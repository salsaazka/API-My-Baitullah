<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    public function fetch(Request $request)
    {
        return ResponseFormatter::success(Auth::user(), 'Data profil user berhasil diambil');
    }

    public function profile()
    {
        return ResponseFormatter::success(Auth::user(), 'Profile retrieved successfully');
    }

    public function updateProfile(Request $request)
    {
        try {
            // Ambil user yang sedang login berdasarkan token JWT
            $user = Auth::user();
    
            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found'
                ], 'Unauthorized', 401);
            }
    
            // Validasi input
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20'
            ]);
    
            // Update data user
            $user->update($validatedData);
    
            return ResponseFormatter::success([
                'user' => $user
            ], 'Berhasil update user');
    
        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Update Failed', 500);
        }
    }
    
    public function updatePhoto(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found or token invalid'
                ], 'Unauthorized', 401);
            }

            $validator = Validator::make($request->all(), [
                'file' => 'required|image|max:2048', // Maksimum 2MB
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error(['error' => $validator->errors()], 'Update Photo Failed', 401);
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '-user.' . $file->getClientOriginalExtension();
                
                // Simpan ke dalam storage/app/public/users/
                $filePath = $file->storeAs('public/users', $fileName);

                // URL yang bisa diakses oleh frontend
                $fileUrl = Storage::url($filePath);
            } else {
                // URL default jika tidak ada gambar yang diunggah
                $fileUrl = 'https://api.wondo.co.id/storage/images/999_pkg_blank.jpg';
            }

            // Update foto profil user
            $user->profile_photo_path = $fileUrl;
            $user->save();

            return ResponseFormatter::success([
                'file_url' => $fileUrl
            ], 'File successfully uploaded');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Upload Failed', 500);
        }
    }

    public function updateKtp(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found or token invalid'
                ], 'Unauthorized', 401);
            }

            $validator = Validator::make($request->all(), [
                'file' => 'required|image|max:2048', // Maksimum 2MB
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error(['error' => $validator->errors()], 'Update Photo Failed', 401);
            }
            
            if ($request->file('file')) {
                // HANDLING FILE IMAGES
                $filesPicturePath = $request->file('file');
                $originName = $filesPicturePath->getClientOriginalName();
                $originName = str_replace(' ', '-', $originName);
                $fileName = pathinfo($originName, PATHINFO_FILENAME);
                $extension = $filesPicturePath->getClientOriginalExtension();
                $fileName = time().'-user--'.$user_id.'.'.$extension;
                $filesPicturePath->move(('documents/ktp'), $fileName);
                $fileName = 'https://api.wondo.co.id/documents/ktp/' . $fileName;
            }
            else{
                $fileName = 'https://api.wondo.co.id/storage/images/999_pkg_blank.jpg';
            }
            
            $merchant = Merchant::where('id_users', $user_id)->first();
            $merchant->photo_ktp = $fileName;
            $merchant->update();
    
            return ResponseFormatter::success([$merchant],'File successfully uploaded');
        
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Auntenticated Failed', 500);
            
        }
        
    }
    

    public function updateRekening(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found or token invalid'
                ], 'Unauthorized', 401);
            }

            // Validasi input
            $validator = Validator::make($request->all(), [
                'no_rekening' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'error' => $validator->errors()
                ], 'Update Rekening Failed', 401);
            }

            // Update nomor rekening user
            $user->no_rekening = $request->no_rekening;
            $user->save();

            return ResponseFormatter::success([
                'user' => $user
            ], 'Berhasil update nomor rekening');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Update Failed', 500);
        }
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return ResponseFormatter::error(['message' => 'Password is incorrect'], 'Password mismatch', 401);
        }

        $user->status = '0';
        $user->status_login = '0';
        $user->save();

        Auth::guard('api')->logout();

        return ResponseFormatter::success([], 'Account deactivated successfully');
    }
    public function getUserById($id)
    {
        $user = User::find($id);
        return response()->json(['user' => $user]);
    }

}
