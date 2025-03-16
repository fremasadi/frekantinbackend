<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImageContent;
use Illuminate\Http\Request;

class ImageContentController extends Controller
{
    /**
     * Mengambil data ImageContent dengan status true.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveImages()
    {
        // Ambil data ImageContent yang statusnya true
        $activeImages = ImageContent::where('status', true)->get();

        // Kembalikan response JSON
        return response()->json([
            'success' => true,
            'message' => 'Data ImageContent dengan status true berhasil diambil.',
            'data' => $activeImages,
        ], 200);
    }
}