<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use App\Services\ContactImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileCrudController extends MobileAppController
{
    /* =====================================================
     | DONASI
     | ===================================================== */

    public function donationCreate()
    {
        $user = auth()->user();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $agents = $this->formAgents($user);
        $contacts = $this->formContacts();

        return view('mobile.forms.donation-form', compact('user', 'branches', 'programs', 'agents', 'contacts'))
            ->with('donation', null);
    }

    public function donationStore(Request $request)
    {
        $data = $this->validateDonation($request);

        if ($request->hasFile('payment_proof')) {
            $data['payment_proof'] = $request->file('payment_proof')->store('donation-proofs', 'public');
        }

        $data['created_by'] = auth()->id();
        $items = $this->normalizeDonationItems($request->input('items'));
        $data['amount'] = round(array_sum(array_column($items, 'amount')), 2);
        $data['program_id'] = $items[0]['program_id'] ?? null;

        $donation = Donation::create($data);

        foreach ($items as $item) {
            $donation->items()->create([
                'program_id' => $item['program_id'],
                'program_category' => $item['program_category'] ?? null,
                'amount' => $item['amount'],
            ]);
        }

        if ($donation->contact_id) {
            $donation->contact()->update(['status' => Contact::STATUS_DONATED]);
        }

        ActivityLog::record('donation.create', 'Mencatat donasi Rp ' . number_format((float) $donation->amount, 0, ',', '.'));

        return redirect()->route('mo.donations')->with('success', 'Donasi berhasil dicatat.');
    }

    public function donationEdit($id)
    {
        $donation = Donation::with('items')->findOrFail($id);

        if (! $this->canAccessDonation($donation)) {
            abort(403, 'Anda hanya dapat mengelola donasi milik sendiri.');
        }

        $user = auth()->user();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $agents = $this->formAgents($user);
        $contacts = $this->formContacts();

        return view('mobile.forms.donation-form', compact('donation', 'user', 'branches', 'programs', 'agents', 'contacts'));
    }

    public function donationUpdate(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);

        if (! $this->canAccessDonation($donation)) {
            abort(403, 'Anda hanya dapat mengelola donasi milik sendiri.');
        }

        $data = $this->validateDonation($request);

        $proofPath = $donation->payment_proof;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('donation-proofs', 'public');
        } elseif ($request->boolean('remove_payment_proof')) {
            $proofPath = null;
        }

        if ($proofPath !== $donation->payment_proof) {
            if ($donation->payment_proof) {
                Storage::disk('public')->delete($donation->payment_proof);
            }
            $data['payment_proof'] = $proofPath;
        }

        $items = $this->normalizeDonationItems($request->input('items'));
        $data['amount'] = round(array_sum(array_column($items, 'amount')), 2);
        $data['program_id'] = $items[0]['program_id'] ?? null;

        $donation->update($data);
        $donation->items()->delete();

        foreach ($items as $item) {
            $donation->items()->create([
                'program_id' => $item['program_id'],
                'program_category' => $item['program_category'] ?? null,
                'amount' => $item['amount'],
            ]);
        }

        if ($donation->contact_id) {
            $donation->contact()->update(['status' => Contact::STATUS_DONATED]);
        }

        ActivityLog::record('donation.update', 'Memperbarui donasi #' . $donation->id);

        return redirect()->route('mo.donations')->with('success', 'Donasi berhasil diperbarui.');
    }

    public function donationDestroy($id)
    {
        $donation = Donation::findOrFail($id);

        if (! $this->canAccessDonation($donation)) {
            abort(403, 'Anda hanya dapat mengelola donasi milik sendiri.');
        }

        if ($donation->payment_proof) {
            Storage::disk('public')->delete($donation->payment_proof);
        }

        ActivityLog::record('donation.delete', 'Menghapus donasi #' . $donation->id);
        $donation->delete();

        return redirect()->route('mo.donations')->with('success', 'Donasi berhasil dihapus.');
    }

    protected function validateDonation(Request $request): array
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.program_id' => ['required', 'exists:programs,id'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
            'items.*.program_category' => ['nullable', 'string'],
            'donation_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'donor_info' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'note' => ['nullable', 'string'],
        ], [
            'items.min' => 'Minimal satu program donasi wajib diisi.',
            'items.*.program_id.required' => 'Setiap baris program wajib memilih program.',
            'items.*.program_id.exists' => 'Program donasi yang dipilih tidak valid.',
            'items.*.amount.required' => 'Nominal donasi wajib diisi.',
            'items.*.amount.min' => 'Nominal donasi minimal Rp 1.',
        ]);

        unset($data['payment_proof']);

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id ?? $data['branch_id'];
        }

        if (! empty($data['agen_id'])) {
            $agen = User::find($data['agen_id']);
            if ($agen && $agen->branch_id && (int) $data['branch_id'] !== (int) $agen->branch_id) {
                $data['branch_id'] = $agen->branch_id;
            }
        }

        return $data;
    }

    protected function normalizeDonationItems($items): array
    {
        if (! is_array($items)) {
            $items = [];
        }

        $items = array_values(array_filter($items, function ($item) {
            return is_array($item)
                && ! empty($item['program_id'])
                && (float) ($item['amount'] ?? 0) > 0;
        }));

        if (count($items) === 0) {
            $items = [[
                'program_id' => null,
                'program_category' => null,
                'amount' => 0,
            ]];
        }

        return $items;
    }

    /* =====================================================
     | KONTAK
     | ===================================================== */

    public function contactCreate()
    {
        $user = auth()->user();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $agents = $this->formAgents($user);

        return view('mobile.forms.contact-form', compact('user', 'branches', 'agents'))->with('contact', null);
    }

    public function contactStore(Request $request)
    {
        $data = $this->validateContact($request, null);

        if (! auth()->user()->isAgen() && ! empty($data['agen_id'])) {
            $agent = User::find($data['agen_id']);
            if ($agent && ! empty($data['branch_id']) && (int) $agent->branch_id !== (int) $data['branch_id']) {
                return back()->withErrors(['agen_id' => 'Agen tidak terdaftar di Cabang yang dipilih.'])->withInput();
            }
            if ($agent && empty($data['branch_id'])) {
                $data['branch_id'] = $agent->branch_id;
            }
        }

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact = Contact::create($data);

        ActivityLog::record('contact.create', 'Membuat kontak ' . $contact->name);

        return redirect()->route('mo.contacts')->with('success', 'Kontak berhasil ditambahkan.');
    }

    public function contactEdit($id)
    {
        $contact = Contact::findOrFail($id);

        if (! $this->canAccessContact($contact)) {
            abort(403, 'Anda hanya dapat mengelola kontak milik sendiri.');
        }

        $user = auth()->user();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $agents = $this->formAgents($user);

        return view('mobile.forms.contact-form', compact('contact', 'user', 'branches', 'agents'));
    }

    public function contactUpdate(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        if (! $this->canAccessContact($contact)) {
            abort(403, 'Anda hanya dapat mengelola kontak milik sendiri.');
        }

        $data = $this->validateContact($request, $contact->id);

        if (! auth()->user()->isAgen() && ! empty($data['agen_id'])) {
            $agent = User::find($data['agen_id']);
            if ($agent && ! empty($data['branch_id']) && (int) $agent->branch_id !== (int) $data['branch_id']) {
                return back()->withErrors(['agen_id' => 'Agen tidak terdaftar di Cabang yang dipilih.'])->withInput();
            }
            if ($agent && empty($data['branch_id'])) {
                $data['branch_id'] = $agent->branch_id;
            }
        }

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact->update($data);

        ActivityLog::record('contact.update', 'Memperbarui kontak ' . $contact->name);

        return redirect()->route('mo.contacts')->with('success', 'Kontak berhasil diperbarui.');
    }

    public function contactDestroy($id)
    {
        $contact = Contact::findOrFail($id);

        if (! $this->canAccessContact($contact)) {
            abort(403, 'Anda hanya dapat mengelola kontak milik sendiri.');
        }

        ActivityLog::record('contact.delete', 'Menghapus kontak ' . $contact->name);
        $contact->delete();

        return redirect()->route('mo.contacts')->with('success', 'Kontak berhasil dihapus.');
    }

    protected function validateContact(Request $request, $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:prospect,contacted,donated,churned'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $service = new ContactImportService();
        $normalized = $service->normalizePhone($data['phone']);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'phone' => 'Format No. WhatsApp tidak valid. Gunakan 10-15 digit angka (contoh: 62812xxxxxxx atau 0812xxxxxxx).',
            ]);
        }

        $data['phone'] = $normalized;

        $map = $service->normalizedPhoneMap();
        if (isset($map[$normalized]) && (int) $map[$normalized]['id'] !== (int) $ignoreId) {
            throw ValidationException::withMessages([
                'phone' => "Nomor WhatsApp sudah terdaftar atas nama '{$map[$normalized]['name']}'.",
            ]);
        }

        return $data;
    }

    /* =====================================================
     | PROGRAM
     | ===================================================== */

    public function programCreate()
    {
        return view('mobile.forms.program-form')->with('program', null);
    }

    public function programStore(Request $request)
    {
        $data = $this->validateProgram($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_goal'] = $request->boolean('show_goal');

        if ($request->hasFile('cover_image')) {
            $data['media'] = [['path' => $request->file('cover_image')->store('programs', 'public'), 'order' => 0]];
        }

        $program = Program::create($data);

        ActivityLog::record('program.create', 'Membuat program ' . $program->name);

        return redirect()->route('mo.programs')->with('success', 'Program berhasil dibuat.');
    }

    public function programEdit($id)
    {
        $program = Program::findOrFail($id);

        return view('mobile.forms.program-form', compact('program'));
    }

    public function programUpdate(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $data = $this->validateProgram($request, $program->id);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_goal'] = $request->boolean('show_goal');

        if ($request->hasFile('cover_image')) {
            $data['media'] = array_merge(
                [['path' => $request->file('cover_image')->store('programs', 'public'), 'order' => 0]],
                collect($program->media ?? [])->reject(fn ($m) => ($m['order'] ?? 0) === 0)->all()
            );
        } elseif ($request->boolean('remove_cover')) {
            $data['media'] = collect($program->media ?? [])->reject(fn ($m) => ($m['order'] ?? 0) === 0)->values()->all();
        }

        $program->update($data);

        ActivityLog::record('program.update', 'Memperbarui program ' . $program->name);

        return redirect()->route('mo.programs')->with('success', 'Program berhasil diperbarui.');
    }

    public function programDestroy($id)
    {
        $program = Program::findOrFail($id);

        if ($program->donationItems()->exists()) {
            return back()->with('error', 'Program tidak dapat dihapus karena masih memiliki donasi.');
        }

        ActivityLog::record('program.delete', 'Menghapus program ' . $program->name);
        $program->delete();

        return redirect()->route('mo.programs')->with('success', 'Program berhasil dihapus.');
    }

    protected function validateProgram(Request $request, $ignoreId = null): array
    {
        $unique = $ignoreId ? 'unique:programs,slug,' . $ignoreId : 'unique:programs,slug';

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $unique],
            'program_category' => ['required', 'in:' . implode(',', array_keys(Program::CATEGORIES))],
            'category' => ['nullable', 'in:penggalangan,penyaluran'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'show_goal' => ['boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    /* =====================================================
     | CABANG
     | ===================================================== */

    public function branchCreate()
    {
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->orderBy('name')->get();

        return view('mobile.forms.branch-form', compact('supervisors'))->with('branch', null);
    }

    public function branchStore(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $branch = Branch::create($data);
        $this->syncSupervisor($branch, $data['supervisor_id'] ?? null);

        ActivityLog::record('branch.create', 'Membuat cabang ' . $branch->name);

        return redirect()->route('mo.branches')->with('success', 'Cabang berhasil dibuat.');
    }

    public function branchEdit($id)
    {
        $branch = Branch::findOrFail($id);
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->orderBy('name')->get();

        return view('mobile.forms.branch-form', compact('branch', 'supervisors'));
    }

    public function branchUpdate(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:branches,code,' . $branch->id],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $branch->update($data);
        $this->syncSupervisor($branch, $data['supervisor_id'] ?? null);

        ActivityLog::record('branch.update', 'Memperbarui cabang ' . $branch->name);

        return redirect()->route('mo.branches')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function branchDestroy($id)
    {
        $branch = Branch::findOrFail($id);

        ActivityLog::record('branch.delete', 'Menghapus cabang ' . $branch->name);
        $branch->delete();

        return redirect()->route('mo.branches')->with('success', 'Cabang berhasil dihapus.');
    }

    protected function syncSupervisor($branch, $supervisorId = null)
    {
        if (! $branch) {
            return;
        }

        if ($supervisorId) {
            User::where('id', $supervisorId)->where('role', 'supervisor')->update(['branch_id' => $branch->id]);
            Branch::where('supervisor_id', $supervisorId)->where('id', '!=', $branch->id)->update(['supervisor_id' => null]);
        } elseif ($branch->supervisor_id) {
            User::where('id', $branch->supervisor_id)->where('role', 'supervisor')->update(['branch_id' => $branch->id]);
        }
    }

    /* =====================================================
     | PENGGUNA
     | ===================================================== */

    public function userCreate()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('mobile.forms.user-form', compact('branches'))->with('editUser', null);
    }

    public function userStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,supervisor,agen,donatur'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = User::uniqueSlug($data['username']);

        $user = User::create($data);
        $this->syncSupervisorBranch($user, $data);

        ActivityLog::record('user.create', 'Membuat user ' . $user->name);

        return redirect()->route('mo.users')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function userEdit($id)
    {
        $editUser = User::findOrFail($id);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('mobile.forms.user-form', compact('editUser', 'branches'));
    }

    public function userUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,supervisor,agen,donatur'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = User::uniqueSlug($data['username'], $user->id);

        $user->update($data);
        $this->syncSupervisorBranch($user, $data);

        ActivityLog::record('user.update', 'Memperbarui user ' . $user->name);

        return redirect()->route('mo.users')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function userDestroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus admin terakhir.');
        }

        ActivityLog::record('user.delete', 'Menghapus user ' . $user->name);
        $user->delete();

        return redirect()->route('mo.users')->with('success', 'Pengguna berhasil dihapus.');
    }

    protected function syncSupervisorBranch(User $user, array $data)
    {
        if ($user->role === User::ROLE_SUPERVISOR) {
            if (! empty($data['branch_id'])) {
                Branch::where('id', $data['branch_id'])->update(['supervisor_id' => $user->id]);
                Branch::where('supervisor_id', $user->id)->where('id', '!=', $data['branch_id'])->update(['supervisor_id' => null]);
            }
        } else {
            Branch::where('supervisor_id', $user->id)->update(['supervisor_id' => null]);
        }
    }

    /* =====================================================
     | HELPER FORM
     | ===================================================== */

    protected function formAgents($user)
    {
        if ($user->isAdmin()) {
            return User::whereIn('role', ['agen', 'supervisor'])->orderBy('name')->get();
        }

        if ($user->isSupervisor()) {
            return User::where('role', 'agen')->where('branch_id', $user->branch_id)->orderBy('name')->get();
        }

        return User::where('id', $user->id)->get();
    }

    protected function formContacts()
    {
        $query = Contact::query();
        $this->scopeContacts($query);

        return $query->orderBy('name')->limit(200)->get();
    }
}
