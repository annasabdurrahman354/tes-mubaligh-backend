<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login with username and password.
     */
    public function loginByCredential(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username atau password tidak valid.'], 401);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil!',
            'token' => $token,
            'user' => $this->transformUser($user),
        ]);
    }

    /**
     * Login with RFID.
     */
    public function loginByRFID(Request $request)
    {
        $request->validate([
            'rfid' => ['required', 'string'],
        ], [
            'rfid.required' => 'Kode RFID wajib diisi.',
            'rfid.string' => 'Kode RFID harus berupa teks.',
        ]);

        $user = User::where('rfid', $request->rfid)->first();

        if (!$user) {
            return response()->json(['message' => 'Smartcard tidak terdata sebagai pengguna.'], 401);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login dengan Smartcard berhasil.',
            'token' => $token,
            'user' => $this->transformUser($user),
        ]);
    }

    /**
     * Logout the currently authenticated user (revoke current token).
     */
    public function logout(Request $request)
    {
        // Revoke the token of the current user
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ], 200);
    }

    /**
     * Logout the user and revoke all tokens.
     */
    public function logoutFromAllDevices(Request $request)
    {
        // Revoke all tokens of the user
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout dari semua perangkat berhasil.',
        ], 200);
    }

    /**
     * Transform user data to include additional attributes.
     */
    private function transformUser(User $user)
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama,
            'nama_panggilan' => $user->nama_panggilan,
            'username' => $user->username,
            'email' => $user->email,
            'nomor_telepon' => $user->nomor_telepon,
            'nik' => $user->nik,
            'rfid' => $user->rfid,
            'pondok_id' => $user->pondok_id,
            'pondok_nama' => $user->pondok?->nama,
            'roles' => $user->roles->pluck('name')->toArray(),
            'foto' => $user->getFilamentAvatarUrl(),  // Add the avatar URL here
        ];
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.string' => 'Password baru harus berupa teks.',
        ]);

        $user = $request->user();

        // Update the password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diperbarui.'], 200);
    }
}
