<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OtpController extends Controller
{

    public function kirim_otp(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'no_tujuan' => 'required|string|max:15',
                'nama_penerima' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Konfigurasi SMS Gateway
            $username = env('GOSMS_USERNAME', 'baitullah'); 
            $password = env('GOSMS_PASSWORD', 'k5a937SV'); 
            $trxid = 'sms.' . Str::uuid();
            $otp_number = rand(1111, 9999);

            // Pesan yang dikirim
            $message = "Hi {$request->nama_penerima}, Here is your Confirmation Code: {$otp_number}. Please do not share this to anyone.";

            // Enkripsi Auth menggunakan md5
            $auth = md5("{$username}{$password}{$request->no_tujuan}");

            // Data yang dikirim ke API
            $data = [
                'username' => $username,
                'mobile' => $request->no_tujuan,
                'message' => $message,
                'auth' => $auth,
                'trxid' => $trxid,
                'type' => "0",
            ];

            // Kirim permintaan ke SMS Gateway
            $response = Http::timeout(5)->post('http://secure.gosmsgateway.com/masking/api/sendSMS.php', $data);

            // Cek respons dari API
            if ($response->successful()) {
                return response()->json([
                    'status' => true,
                    'message' => 'OTP sent successfully',
                    'otp' => $otp_number
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send OTP',
                    'error' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    
    }
    // public function updateOtpRegister(Request $request, $id)
    // {
    //     try {
    //         // Ambil user dari token JWT
    //         $user = auth()->user();

    //         // Pastikan user sesuai dengan yang diberikan di parameter
    //         if (!$user || $user->id != $id) {
    //             return ResponseFormatter::error([
    //                 'message' => 'User not found or token invalid'
    //             ], 'Unauthorized', 401);
    //         }

    //         // Ambil data OTP dari database
    //         $get_data_otp = DB::table('trs_gps_anggota as A')
    //             ->where('A.id_users', $id)
    //             ->where('A.status', 'aktif')
    //             ->where('A.absen', '1')
    //             ->leftJoin('tabel_channel_gps as B', 'A.id_channel_gps', '=', 'B.id_channel_gps')
    //             ->leftJoin('users as C', 'B.id_users', '=', 'C.id')
    //             ->select('C.id as id_ustad', 'A.id_channel_gps', 'B.kode_channel', 'B.nama_channel', 'C.name as nama_ustad', 'C.remember_device_id')
    //             ->first();

    //         if (!$get_data_otp) {
    //             return ResponseFormatter::error([
    //                 'message' => 'No OTP data found'
    //             ], 'Not Found', 404);
    //         }

    //         return ResponseFormatter::success([
    //             'user' => $user,
    //             'datas_gps' => $get_data_otp
    //         ], 'Berhasil mendapatkan OTP data');

    //     } catch (\Exception $error) {
    //         return ResponseFormatter::error([
    //             'message' => 'Something went wrong',
    //             'error' => $error->getMessage()
    //         ], 'Update Failed', 500);
    //     }

    // }
}