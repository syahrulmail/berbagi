<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('activity-logs.index', compact('logs'));
    }
}
