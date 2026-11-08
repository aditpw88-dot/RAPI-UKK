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

    public function getIsActiveAttribute(string $value): bool
    {
        return $value === 'Aktif' || $value === '1';
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
}
