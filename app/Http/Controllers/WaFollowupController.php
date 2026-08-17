<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\User;
use App\Models\WaFollowup;
use Illuminate\Http\Request;

class WaFollowupController extends Controller
{
    public function index(Request $request)
    {
        $query = WaFollowup::query()->with(['agen', 'program']);

        $user = auth()->user();
        if ($user->role == 'agen') {
            $query->where('agen_id', $user->id);
        }

        if ($request->filled('agen_id')) {
            $query->where('agen_id', $request->agen_id);
        }
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $followups = $query->orderByDesc('created_at')->paginate(20);

        $agens = User::whereIn('role', ['agen', 'supervisor'])->orderBy('name')->get();
        $programs = Program::orderBy('name')->get();

        return view('followups.index', compact('followups', 'agens', 'programs'));
    }

    public function destroy(WaFollowup $followup)
    {
        $user = auth()->user();
        if ($user->role == 'agen' && $followup->agen_id != $user->id) {
            abort(403);
        }

        $followup->delete();

        return redirect()->route('followups.index')
            ->with('success', 'Data follow-up dihapus.');
    }
}
