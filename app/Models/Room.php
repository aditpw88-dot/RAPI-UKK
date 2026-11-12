<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class Room extends Model
{
    protected $fillable = [
        'room_name',
        'capacity',
        'pic_id',
        'is_active',
        'maintenance_note',
    ];

    protected static function booted(): void
    {
        static::saved(function (Room $room) {
            if ($room->pic_id && \Illuminate\Support\Facades\Schema::hasTable('room_pic')) {
                \Illuminate\Support\Facades\DB::table('room_pic')->insertOrIgnore([
                    'room_id' => $room->id,
                    'user_id' => $room->pic_id,
                ]);
            }
        });
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function pics(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_pic', 'room_id', 'user_id');
    }

    public function getAllPicsAttribute(): Collection
    {
        $pics = $this->relationLoaded('pics') ? $this->pics : $this->pics()->get();
        if ($pics->isEmpty() && $this->pic) {
            return collect([$this->pic]);
        }
        return $pics;
    }

    public function getPicNamesAttribute(): string
    {
        $pics = $this->all_pics;
        if ($pics->isEmpty()) {
            return '-';
        }
        return $pics->pluck('fullname')->join(', ');
    }

    public function getPicPhoneAttribute(): ?string
    {
        $phone = $this->pic?->phone_number;
        if (! $phone) {
            $firstPicWithPhone = $this->all_pics->first(fn ($u) => ! empty($u->phone_number));
            $phone = $firstPicWithPhone?->phone_number;
        }
        return $phone;
    }

    public function getPicWhatsappUrlAttribute(): ?string
    {
        $phone = $this->pic_phone;
        if (! $phone) {
            return null;
        }
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (! $cleanPhone) {
            return null;
        }
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
        $msg = rawurlencode("Halo Bapak/Ibu PIC {$this->room_name}, saya ingin menanyakan perihal ruangan {$this->room_name} yang sedang dalam perbaikan.");
        return "https://wa.me/{$cleanPhone}?text={$msg}";
    }

    public function getIsActiveAttribute(string $value): bool
    {
        return $value === 'Aktif' || $value === '1';
    }

    public function getCurrentEventAttribute(): ?Event
    {
        if (array_key_exists('current_event', $this->attributes)) {
            return $this->attributes['current_event'];
        }

        if (! $this->is_active) {
            return null;
        }

        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        return Event::forRooms([$this->id])
            ->where('status', 'approved')
            ->whereNull('completed_at')
            ->where(function ($q) use ($today) {
                $q->where('date', '<=', $today)
                    ->where(function ($sub) use ($today) {
                        $sub->whereNull('end_date')
                            ->orWhere('end_date', '>=', $today);
                    });
            })
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->with(['pic'])
            ->first();
    }

    public function getOngoingEvent(): ?Event
    {
        if (array_key_exists('current_event', $this->attributes) && $this->attributes['current_event'] instanceof Event) {
            return $this->attributes['current_event'];
        }

        return Event::forRooms([$this->id])
            ->approvedInProgress()
            ->with(['pic'])
            ->first();
    }

    public function getIsAvailableAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->current_event === null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_available ? 'Tersedia' : 'Tidak tersedia';
    }

    public function getConditionLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Tidak aktif';
    }

    public static function sortRooms(iterable $rooms): Collection
    {
        return collect($rooms)->sort(function ($a, $b) {
            $nameA = is_array($a) ? ($a['room_name'] ?? '') : ($a->room_name ?? '');
            $nameB = is_array($b) ? ($b['room_name'] ?? '') : ($b->room_name ?? '');

            return self::compareRoomNames($nameA, $nameB);
        })->values();
    }

    public static function paginateRooms(iterable $rooms, int $perPage = 10, ?Request $request = null): LengthAwarePaginator
    {
        $sorted = self::sortRooms($rooms);
        $page = Paginator::resolveCurrentPage() ?: 1;
        $items = $sorted->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        if ($request) {
            $paginator->appends($request->query());
        } else {
            $paginator->withQueryString();
        }

        return $paginator;
    }

    public static function compareRoomNames(string $nameA, string $nameB): int
    {
        $getRank = function (string $rawName): array {
            $name = trim(preg_replace('/\s+/', ' ', $rawName));
            $lower = strtolower($name);

            // 1. Ruang Wijaya
            if (str_contains($lower, 'wijaya')) {
                return [0, 0, $lower];
            }

            // 2. DAK
            if (str_contains($lower, 'dak')) {
                return [0, 1, $lower];
            }

            // 3. Lab 1 - 21
            if (preg_match('/^lab\s*(\d+)/i', $name, $matches)) {
                return [1, (int) $matches[1], $lower];
            }

            // 4. Ruang 1 - seterusnya (termasuk Ruang Kelas 1 - 32 atau Ruang 1 - n)
            if (preg_match('/^(?:ruang\s+kelas|ruang|kelas)\s*(\d+)/i', $name, $matches)) {
                return [2, (int) $matches[1], $lower];
            }

            // 5. Ruangan lainnya
            return [3, 0, $lower];
        };

        $rankA = $getRank($nameA);
        $rankB = $getRank($nameB);

        if ($rankA[0] !== $rankB[0]) {
            return $rankA[0] <=> $rankB[0];
        }

        if ($rankA[1] !== $rankB[1]) {
            return $rankA[1] <=> $rankB[1];
        }

        return strnatcasecmp($nameA, $nameB);
    }

    public static function calculateDistance(Room $a, Room $b): int
    {
        if ($a->id === $b->id) {
            return 0;
        }

        $nameA = trim(strtolower($a->room_name));
        $nameB = trim(strtolower($b->room_name));

        if ($nameA === $nameB) {
            return 0;
        }

        // Kategori sama: Lab (misal Lab 1 ke Lab 2 berjarak 1)
        $isLabA = preg_match('/^lab\s*(\d+)/i', $nameA, $matchA);
        $isLabB = preg_match('/^lab\s*(\d+)/i', $nameB, $matchB);
        if ($isLabA && $isLabB) {
            return abs((int) $matchA[1] - (int) $matchB[1]);
        }

        // Kategori sama: Ruang Kelas / Kelas
        $isClassA = preg_match('/^(?:ruang\s+kelas|ruang|kelas)\s*(\d+)/i', $nameA, $matchClassA);
        $isClassB = preg_match('/^(?:ruang\s+kelas|ruang|kelas)\s*(\d+)/i', $nameB, $matchClassB);
        if ($isClassA && $isClassB) {
            return abs((int) $matchClassA[1] - (int) $matchClassB[1]);
        }

        // Ruang Wijaya & DAK
        $isHallA = str_contains($nameA, 'wijaya') || str_contains($nameA, 'dak');
        $isHallB = str_contains($nameB, 'wijaya') || str_contains($nameB, 'dak');
        if ($isHallA && $isHallB) {
            return 5;
        }

        // Antar tipe ruangan berbeda, hitung selisih jarak fallback
        return 1000 + abs((int) $a->id - (int) $b->id);
    }

    public static function findNearestAvailableRoom(
        Room $sourceRoom,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        array $excludeRoomIds = []
    ): ?Room {
        $candidateRooms = self::where('is_active', 'Aktif')
            ->whereNotIn('id', array_unique(array_merge([$sourceRoom->id], $excludeRoomIds)))
            ->get();

        if ($candidateRooms->isEmpty()) {
            return null;
        }

        $candidateIds = $candidateRooms->pluck('id')->all();

        // Cari agenda yang bentrok pada jadwal tersebut
        $overlappingEvents = Event::forRooms($candidateIds)
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(fn ($q) => $q->where('status', 'approved')->whereNull('completed_at'));
            })
            ->overlappingSchedule($startDate, $endDate, $startTime, $endTime)
            ->with('rooms')
            ->get();

        $busyRoomIds = [];
        foreach ($overlappingEvents as $ev) {
            foreach ($ev->reservedRoomIds() as $rId) {
                $busyRoomIds[(int) $rId] = true;
            }
        }

        $availableRooms = $candidateRooms->filter(fn (Room $r) => ! isset($busyRoomIds[(int) $r->id]));

        if ($availableRooms->isEmpty()) {
            return null;
        }

        return $availableRooms->sortBy(fn (Room $candidate) => self::calculateDistance($sourceRoom, $candidate))->first();
    }
}
