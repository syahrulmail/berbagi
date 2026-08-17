<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::with(['agen', 'branch']);

        if (auth()->user()->isAgen()) {
            $query->where('agen_id', auth()->id());
        } elseif (auth()->user()->isSupervisor() && auth()->user()->branch_id) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        $query->when($request->status, function ($q, $status) {
            return $q->where('status', $status);
        })
        ->when($request->search, function ($q, $search) {
            return $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        });

        $contacts = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $agents = $this->visibleAgents();

        return view('contacts.index', compact('contacts', 'agents'));
    }

    public function create()
    {
        $agents = $this->visibleAgents();

        return view('contacts.create', compact('agents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:prospect,contacted,donated,churned'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact = Contact::create($data);

        ActivityLog::record('contact.create', 'Membuat kontak ' . $contact->name);

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil ditambahkan.');
    }

    public function edit(Contact $contact)
    {
        $this->authorizeAccess($contact);

        $agents = $this->visibleAgents();

        return view('contacts.edit', compact('contact', 'agents'));
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorizeAccess($contact);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:prospect,contacted,donated,churned'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact->update($data);

        ActivityLog::record('contact.update', 'Memperbarui kontak ' . $contact->name);

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    public function destroy(Contact $contact)
    {
        $this->authorizeAccess($contact);

        ActivityLog::record('contact.delete', 'Menghapus kontak ' . $contact->name);
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil dihapus.');
    }

    protected function visibleAgents()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return User::where('role', 'agen')->orderBy('name')->get();
        }

        if ($user->isSupervisor()) {
            return User::where('role', 'agen')
                ->where('branch_id', $user->branch_id)
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    protected function authorizeAccess(Contact $contact): void
    {
        $user = auth()->user();

        if ($user->isAgen() && (int) $contact->agen_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat mengelola kontak milik sendiri.');
        }

        if ($user->isSupervisor() && $contact->branch_id !== $user->branch_id) {
            abort(403, 'Anda hanya dapat mengelola kontak di cabang Anda.');
        }
    }
}
