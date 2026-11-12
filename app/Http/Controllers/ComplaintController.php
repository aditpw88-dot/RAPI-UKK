<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensurePic();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Belum Diperbaiki,Sedang Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki'],
        ]);
        $search = trim($filters['search'] ?? '');
        $picId = auth()->id();
        $threshold = now()->subHours(24);

        $visibleQuery = function ($query) use ($threshold) {
            $query->where(function ($q) use ($threshold) {
                $q->where('status_repair', '!=', 'Sudah Diperbaiki')
                    ->orWhere(function ($sub) use ($threshold) {
                        $sub->where('status_repair', 'Sudah Diperbaiki')
                            ->where(function ($timeQ) use ($threshold) {
                                $timeQ->where('repaired_at', '>=', $threshold)
                                    ->orWhere(function ($fallbackQ) use ($threshold) {
                                        $fallbackQ->whereNull('repaired_at')
                                            ->where('updated_at', '>=', $threshold);
                                    });
                            });
                    });
            });
        };

        $picScope = function ($query) use ($picId) {
            $query->where(function ($q) use ($picId) {
                $q->whereHas('room', fn ($roomQ) => $roomQ->where('pic_id', $picId))
                    ->orWhere('handler_id', $picId);
            });
        };

        $complaints = Complaint::with(['room', 'reporter.role'])
            ->where($picScope)
            ->where($visibleQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($query) => $query->where('room_name', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn ($query) => $query
                            ->where('fullname', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status_repair', $filters['status']))
            ->latest('reported_at')
            ->paginate(10)
            ->withQueryString();

        $statistics = [
            'total' => Complaint::where($picScope)->where($visibleQuery)->count(),
            'not_repaired' => Complaint::where($picScope)->where('status_repair', 'Belum Diperbaiki')->count(),
            'in_repair' => Complaint::where($picScope)->where('status_repair', 'Sedang Diperbaiki')->count(),
            'waiting_parts' => Complaint::where($picScope)->where('status_repair', 'Menunggu Spare Part')->count(),
            'repaired' => Complaint::where($picScope)
                ->where('status_repair', 'Sudah Diperbaiki')
                ->where(function ($timeQ) use ($threshold) {
                    $timeQ->where('repaired_at', '>=', $threshold)
                        ->orWhere(function ($fallbackQ) use ($threshold) {
                            $fallbackQ->whereNull('repaired_at')
                                ->where('updated_at', '>=', $threshold);
                        });
                })->count(),
        ];

        return view('pic.complaints.index', compact('complaints', 'statistics'));
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $this->ensurePic();
        $complaint = Complaint::with('room')->findOrFail($id);

        if ((int) $complaint->room->pic_id !== (int) auth()->id()
            && (int) $complaint->handler_id !== (int) auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui laporan ini.');
        }

        $validated = $request->validate([
            'status_repair' => [
                'required',
                'in:Belum Diperbaiki,Sedang Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki',
            ],
        ]);

        $statusRepair = $validated['status_repair'];
        $repairedAt = $complaint->repaired_at;
        if ($statusRepair === 'Sudah Diperbaiki') {
            $repairedAt = $repairedAt ?: now();
        } else {
            $repairedAt = null;
        }

        $complaint->update([
            'status_repair' => $statusRepair,
            'handler_id' => auth()->id(),
            'repaired_at' => $repairedAt,
            'is_escalated' => $statusRepair === 'Belum Diperbaiki' && $complaint->is_escalated,
        ]);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
    }

    public function create(Request $request): View
    {
        $this->ensureCanCreateComplaint();
        $rooms = Room::sortRooms(Room::where('is_active', 'Aktif')->get());
        $selectedRoomId = Room::where('is_active', 'Aktif')
            ->whereKey($request->query('room_id'))
            ->value('id');

        return view('guru.complaints.create', compact('rooms', 'selectedRoomId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanCreateComplaint();

        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $roomIsActive = Room::whereKey($validated['room_id'])->where('is_active', 'Aktif')->exists();
        if (! $roomIsActive) {
            return back()->withInput()->withErrors(['room_id' => 'Ruang yang dipilih sedang tidak aktif.']);
        }

        $path = $request->file('photo')->store('complaints', 'public');

        Complaint::create([
            'room_id' => $validated['room_id'],
            'reporter_id' => $request->user()->id,
            'description' => $validated['description'],
            'photo_path' => $path,
            'status_repair' => 'Belum Diperbaiki',
        ]);

        $destination = str_contains(strtolower($request->user()->role?->role_name ?? ''), 'admin')
            ? 'admin.complaints'
            : 'guru.dashboard';

        return redirect()->route($destination)->with('success', 'Laporan kerusakan berhasil dikirim.');
    }

    private function ensureGuru(): void
    {
        if (! str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'guru')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Guru/Organisasi.');
        }
    }

    private function ensureCanCreateComplaint(): void
    {
        $roleName = strtolower(auth()->user()->role?->role_name ?? '');

        if (! str_contains($roleName, 'guru') && ! str_contains($roleName, 'admin')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh Admin atau Guru/Organisasi.');
        }
    }

    private function ensurePic(): void
    {
        if (! str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'pic')) {
            abort(403, 'Halaman ini hanya dapat diakses oleh PIC Ruang.');
        }
    }
}
