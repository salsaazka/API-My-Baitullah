<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function __invoke(Request $request)
    // {
    //     //set validation
    //     $validator = Validator::make($request->all(), [
    //         'name'      => 'required|string|max:255',
    //         'email'     => 'required|email|unique:users',
    //         'password'  => 'required|min:6'
    //     ]);

    //     //if validation fails
    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     //create user
    //     $user = User::create([
    //         'name'      => $request->name,
    //         'email'     => $request->email,
    //         'phone'     => $request->phone,
    //         'password'  => bcrypt($request->password)
    //     ]);

    //     //return response JSON user is created
    //     if($user) {
    //         return response()->json([
    //             'success' => true,
    //             'user'    => $user,  
    //         ], 201);
    //     }

    //     //return JSON process insert failed 
    //     return response()->json([
    //         'success' => false,
    //     ], 409);
    // }

    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'name' => 'required|string|max:255',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "status_code" => 400,
                "message" => "Validation failed",
                "errors" => $validator->errors()
            ], 400);
        }
        $uuidUser = Str::uuid();

        // Buat user baru
        $create_users = new User();
        $create_users->uuid = $uuidUser;
        $create_users->name = $request->name;
        $create_users->email = $request->email;
        $create_users->phone = $request->phone;
        $create_users->source_daftar = "apps";
        $create_users->status_login = "2";
        $create_users->password = Hash::make($request->password);
        $create_users->save();

        // Ambil user baru untuk membuat token
        $user = User::where('email', $request->email)->first();
        $token = Str::random(60); // Membuat token secara manual
        DB::table('personal_access_tokens')->insert([
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'authToken',
            'token' => hash('sha256', $token), // Simpan dalam format hash untuk keamanan
            'abilities' => json_encode(["*"]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $tokenResult = $token;
        return response()->json([
            "status" => "success",
            "status_code" => 200,
            "message" => "Register Berhasil",
            'token_type' => 'Bearer',
            "access_token" => $tokenResult,
            'user' => $user
        ], 200);
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