<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User; // Menggunakan model Room dari folder Models Anda
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Mengambil semua data dari tabel rooms
        $rooms = Room::all();

        return response()->json([
            'success' => true,
            'message' => 'List Data User',
            'data'    => $rooms
        ], 200);
    }
}
