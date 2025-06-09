<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

// Pastikan untuk mengimpor model User Anda
// Impor Log facade
// Note: Validator facade is no longer directly needed in methods if using $request->validate()
// use Illuminate\Support\Facades\Validator;
// use Illuminate\Validation\Rules\Password; // Not used in the provided code snippet

class UserProfileController extends Controller
{
    /**
     * Perbarui username pengguna yang terautentikasi.
     */
    public function updateUsername(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $validated = $request->validate([
                'current_username' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                    if ($value !== $user->username) {
                        // Pesan dalam Bahasa Indonesia
                        $fail('Isian tidak cocok dengan username Anda saat ini.');
                    }
                }],
                'new_username' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tes_users', 'username') // Pastikan 'tes_users' adalah nama tabel yang benar
                    ->ignore($user->id)
                    // ->whereNull('deleted_at') // Tambahkan ini jika Anda menggunakan SoftDeletes dan memerlukannya
                ],
            ], [
                // Custom validation messages in Indonesian
                'current_username.required' => 'Username saat ini wajib diisi.',
                'new_username.required' => 'Username baru wajib diisi.',
                'new_username.string' => 'Username baru harus berupa teks.',
                'new_username.max' => 'Username baru maksimal :max karakter.',
                'new_username.unique' => 'Username baru sudah digunakan.',
            ]);

            $user->username = $validated['new_username'];
            $user->save();

            return response()->json([
                'message' => 'Username berhasil diperbarui.',
                'user' => $this->transformUser($user->fresh()), // Return transformed updated user data
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Laravel's default $request->validate() throws this exception
            // It automatically returns a 422 response with errors, but we can customize if needed
            // return response()->json(['message' => 'Data yang diberikan tidak valid.', 'errors' => $e->errors()], 422);
            // Or let Laravel handle the default JSON response for validation errors
            throw $e;
        } catch (Exception $e) {
            Log::error('Error memperbarui username untuk user ID ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui username. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Perbarui password pengguna yang terautentikasi.
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $validated = $request->validate([
                'current_password' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        // Pesan dalam Bahasa Indonesia
                        $fail('Password saat ini tidak benar.');
                    }
                }],
                'new_password' => [
                    'required',
                    'string',
                    'confirmed' // Requires 'new_password_confirmation' field
                    // Add complexity rules if needed: Password::min(8)->mixedCase()->numbers()->symbols()
                ],
            ], [
                // Custom validation messages in Indonesian
                'current_password.required' => 'Password saat ini wajib diisi.',
                'new_password.required' => 'Password baru wajib diisi.',
                'new_password.string' => 'Password baru harus berupa teks.',
                'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
                // Add messages for complexity rules if used
            ]);

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Optional: Logout user from other devices/sessions if desired
            // Auth::logoutOtherDevices($validated['current_password']); // Be cautious with this

            return response()->json(['message' => 'Password berhasil diperbarui.']); // No sensitive data needed in response

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle the 422 JSON response
        } catch (Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui password. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Perbarui foto profil pengguna yang terautentikasi.
     */
    public function updatePhoto(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Max 2MB example
            ], [
                // Custom validation messages in Indonesian
                'photo.required' => 'File foto wajib diunggah.',
                'photo.image' => 'File yang diunggah harus berupa gambar.',
                'photo.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp.',
                'photo.max' => 'Ukuran gambar maksimal 2MB.',
            ]);

            // Clear existing photo(s) in the 'user_foto' collection first
            $user->clearMediaCollection('user_foto');

            // Add the new photo from the request
            $user->addMediaFromRequest('photo')
                ->toMediaCollection('user_foto');

            // Refresh the user model to get the latest media URL
            $user->refresh();

            return response()->json([
                'message' => 'Foto profil berhasil diperbarui.',
                'avatar_url' => $user->getFilamentAvatarUrl(), // Use the method from your User model
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle the 422 JSON response
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist $e) {
            return response()->json(['message' => 'File yang diunggah tidak ditemukan.'], 400); // Bad Request
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig $e) {
            return response()->json(['message' => 'Ukuran file yang diunggah terlalu besar.'], 413); // Payload Too Large
        } catch (Exception $e) {
            Log::error('Error memperbarui foto profil untuk user ID ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui foto profil. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Perbarui RFID pengguna yang terautentikasi.
     */
    public function updateRfid(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $validated = $request->validate([
                'new_rfid' => [
                    'required',
                    'numeric', // Assuming RFID is numeric
                    'max_digits:11', // Match maxLength(11) for numeric
                    Rule::unique('tes_users', 'rfid') // Ensure 'tes_users' is correct table name
                    ->ignore($user->id)
                        ->whereNull('deleted_at') // Align unique rule with your form/model logic
                ],
            ], [
                // Custom validation messages in Indonesian
                'new_rfid.required' => 'Kode RFID baru wajib diisi.',
                'new_rfid.numeric' => 'Kode RFID baru harus berupa angka.',
                'new_rfid.max_digits' => 'Kode RFID baru maksimal 11 digit.',
                'new_rfid.unique' => 'Kode RFID baru sudah terdaftar.',
            ]);

            $user->rfid = $validated['new_rfid'];
            $user->save();

            return response()->json([
                'message' => 'RFID berhasil diperbarui.',
                'user' => $this->transformUser($user->fresh()), // Return transformed updated user data
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle the 422 JSON response
        } catch (Exception $e) {
            Log::error('Error memperbarui RFID untuk user ID ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
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
}
