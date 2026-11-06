<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $statistics = [
            'users' => User::count(),
            'rooms' => Room::count(),
            'active_rooms' => Room::where('is_active', 'Aktif')->count(),
            'events' => Event::count(),
            'pending_events' => Event::where('status', 'pending')->count(),
            'complaints' => Complaint::count(),
            'open_complaints' => Complaint::where('status_repair', '!=', 'Sudah Diperbaiki')->count(),
        ];

        $latestEvents = Event::with(['room', 'rooms'])->latest('created_at')->limit(5)->get();
        $latestComplaints = Complaint::with(['room', 'reporter.role'])->latest('reported_at')->limit(5)->get();

        return view('dashboard', compact('statistics', 'latestEvents', 'latestComplaints'));
    }
}
