<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\GuruDashboardController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\PicComplaintReportController;
use App\Http\Controllers\PicDashboardController;
use App\Models\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $events = [];

    if (Schema::hasTable('events') && Schema::hasTable('rooms')) {
        $events = Event::with(['room', 'rooms', 'pic'])
            ->where('status', 'approved')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->event_name,
                    'eventName' => $event->event_name,
                    'roomName' => $event->room_id ? ($event->room_names ?: 'Ruang') : 'Seluruh sekolah',
                    'eventType' => $event->event_type,
                    'picName' => $event->pic?->fullname ?? $event->pic?->name ?? 'Pihak Sekolah',
                    'createdAt' => $event->created_at?->toIso8601String() ?? '',
                    'event_type' => $event->event_type,
                    'start' => $event->room_id ? $event->date.'T'.$event->start_time : $event->date,
                    'end' => $event->room_id
                        ? ($event->end_date ?? $event->date).'T'.$event->end_time
                        : \Illuminate\Support\Carbon::parse($event->end_date ?? $event->date)->addDay()->toDateString(),
                    'allDay' => $event->room_id === null,
                    'date' => $event->date,
                    'end_date' => $event->end_date ?? $event->date,
                    ...$event->calendarColors(),
                ];
            });
    }

    return view('welcome', compact('events'));
})->name('home');

Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('/calendar/holidays', [CalendarController::class, 'holidays'])->name('calendar.holidays');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $roleName = strtolower(auth()->user()->role?->role_name ?? '');

        if (str_contains($roleName, 'pic')) {
            return redirect()->route('pic.dashboard');
        }

        if (str_contains($roleName, 'admin') || str_contains($roleName, 'waka')) {
            return redirect()->route('admin.dashboard');
        }

        if (str_contains($roleName, 'guru')) {
            return redirect()->route('guru.dashboard');
        }

        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('admin')
        ->name('admin.dashboard');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/rooms', [AdminManagementController::class, 'rooms'])->name('admin.rooms');
        Route::post('/rooms', [AdminManagementController::class, 'storeRoom'])->name('admin.rooms.store');
        Route::put('/rooms/{room}', [AdminManagementController::class, 'updateRoom'])->name('admin.rooms.update');
        Route::delete('/rooms/{room}', [AdminManagementController::class, 'destroyRoom'])->name('admin.rooms.destroy');
        Route::get('/events', [AdminManagementController::class, 'events'])->name('admin.events');
        Route::get('/events/create', [AdminManagementController::class, 'createEvent'])->name('admin.events.create');
        Route::post('/events', [AdminManagementController::class, 'storeEvent'])->name('admin.events.store');
        Route::post('/school-events', [AdminManagementController::class, 'storeSchoolEvent'])->name('admin.school-events.store');
        Route::post('/events/{event}/approve', [AdminManagementController::class, 'approveEvent'])->name('admin.events.approve');
        Route::patch('/events/{event}/move-reason', [AdminManagementController::class, 'saveMoveReason'])->name('admin.events.move-reason');
        Route::patch('/events/{event}/move', [AdminManagementController::class, 'moveEvent'])->name('admin.events.move');
        Route::delete('/events/{event}', [AdminManagementController::class, 'destroyEvent'])->name('admin.events.destroy');
        Route::get('/complaints', [AdminManagementController::class, 'complaints'])->name('admin.complaints');
        Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('admin.complaints.create');
        Route::post('/complaints', [ComplaintController::class, 'store'])->name('admin.complaints.store');
        Route::post('/complaints/{complaint}/remind', [AdminManagementController::class, 'remindComplaintHandler'])->name('admin.complaints.remind');
        Route::patch('/complaints/{complaint}/reassign', [AdminManagementController::class, 'reassignEscalatedComplaint'])->name('admin.complaints.reassign');
    });

    Route::middleware('admin')->prefix('waka')->group(function () {
        Route::get('/reports/monthly', [MonthlyReportController::class, 'index'])->name('reports.monthly');
        Route::get('/reports/monthly/csv', [MonthlyReportController::class, 'exportCsv'])->name('reports.monthly.csv');
        Route::get('/reports/monthly/pdf', [MonthlyReportController::class, 'exportPdf'])->name('reports.monthly.pdf');
    });

    Route::get('/pic/dashboard', [PicDashboardController::class, 'index'])
        ->name('pic.dashboard');
    Route::get('/pic/approvals', [PicDashboardController::class, 'approvals'])
        ->name('pic.approvals');
    Route::get('/pic/approved-events', [PicDashboardController::class, 'approvedEvents'])
        ->name('pic.events.approved');
    Route::get('/guru/dashboard', [GuruDashboardController::class, 'index'])
        ->name('guru.dashboard');
    Route::get('/guru/events/history', [GuruDashboardController::class, 'eventHistory'])
        ->name('guru.events.history');
    Route::get('/guru/complaints/status', [GuruDashboardController::class, 'repairStatus'])
        ->name('guru.complaints.status');
    Route::get('/guru/complaints/create', [ComplaintController::class, 'create'])
        ->name('guru.complaints.create');
    Route::post('/guru/complaints', [ComplaintController::class, 'store'])
        ->name('guru.complaints.store');
    Route::get('/pic/calendar', [CalendarController::class, 'picIndex'])
        ->name('pic.calendar');
    Route::get('/pic/complaints', [ComplaintController::class, 'index'])
        ->name('pic.complaints.index');
    Route::get('/pic/reports/complaints', [PicComplaintReportController::class, 'index'])
        ->name('pic.reports.complaints');
    Route::get('/pic/reports/complaints/csv', [PicComplaintReportController::class, 'exportCsv'])
        ->name('pic.reports.complaints.csv');
    Route::get('/pic/reports/complaints/pdf', [PicComplaintReportController::class, 'exportPdf'])
        ->name('pic.reports.complaints.pdf');
    Route::patch('/pic/complaints/{id}/status', [ComplaintController::class, 'updateStatus'])
        ->name('pic.complaints.update-status');
    Route::post('/pic/events/{event}/approve', [PicDashboardController::class, 'approve'])
        ->name('pic.events.approve');
    Route::post('/pic/events/{event}/reject', [PicDashboardController::class, 'reject'])
        ->name('pic.events.reject');
});

require __DIR__.'/auth.php';
