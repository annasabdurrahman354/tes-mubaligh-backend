<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class WhatsAppController extends Controller
{
    public function send(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'phoneNumber' => 'required|string',
            'provinsi' => 'required|string',
            'nama' => 'required|string',
            'nomorUrut' => 'required|numeric',
        ]);

        $phone = $request->phoneNumber;
        $provinsi = $request->provinsi;
        $nama = $request->nama;
        $nomor = $request->nomorUrut;

        $message = "*📢 PANGGILAN ANTRIAN!* %0A" .
            "👤 *{$nama}* %0A" .
            "📍 *{$provinsi}* %0A" .
            "🔢 *Nomor: {$nomor}* %0A" .
            "Silakan ke kantor untuk pemrosesan laporan keuangan daerah Anda!";

        $apiKey = "9161977"; // Ganti dengan API key CallMeBot milikmu

        $url = "https://api.callmebot.com/whatsapp.php";

        try {
            $response = Http::get($url, [
                'phone' => $phone,
                'text' => $message,
                'apikey' => $apiKey
            ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Pesan berhasil dikirim']);
            } else {
                return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
