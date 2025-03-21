<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\VoiceChannel;
use App\Models\TrsVoiceAnggota;
use App\Models\TrsVoiceOnline;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class VoiceController extends Controller
{
    public function create(Request $request)
    {
        try {
            
            $user = JWTAuth::parseToken()->authenticate();

            $validator = Validator::make($request->all(), [
                'id_travel' => 'required|integer',
                'nama_channel' => 'required|string',
                'nama_channel_opsi' => 'nullable|string',
                'kepala_group' => 'required|string',
                'passcode' => 'required|string',
                'selesai_channel' => 'required|date',
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'errors' => $validator->errors()
                ], 'Validation Failed', 422);
            }

            // Simpan data
            $voice = new VoiceChannel();
            $voice->kode_channel = "BaitullahVOICE_" . uniqid();
            $voice->id_travel = $request->id_travel;
            $voice->nama_channel = $request->nama_channel;
            $voice->nama_channel_opsi = $request->nama_channel_opsi;
            $voice->kepala_group = $request->kepala_group;
            $voice->passcode = $request->passcode;
            $voice->id_users = $user->id;
            $voice->id_paket = $request->id_paket;
            $voice->mulai_channel = Carbon::now()->toDateString();
            $voice->selesai_channel = $request->selesai_channel;
            $voice->status = "tidak_aktif";
            $voice->author = strtolower($user->email);
            $voice->updater = strtolower($user->email);
            $voice->save();

            return ResponseFormatter::success($voice, 'Berhasil di Input');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Auntenticated Failed', 500);
        }
    }

    public function getAllData(Request $request)
    {
        try {
            $filter_today = Carbon::now()->toDateString();
            $status_channel = $request->query('status_channel');

            $datas = VoiceChannel::query();

            if ($request->input('validate')) {
                $datas->where('selesai_channel', '>=', $filter_today);
            }

            if ($status_channel) {
                $datas->where('status', $status_channel);
            }

            $datas->orderBy('mulai_channel', 'asc');

            return ResponseFormatter::success($datas->get(), 'Berhasil show data');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Gagal show data', 500);
        }
    }

    public function getMyData(Request $request)
    {
        try {
            // Ambil user yang sedang login
            $user = JWTAuth::parseToken()->authenticate();

            $datas = VoiceChannel::where('id_users', $user->id)
                ->orderBy('mulai_channel', 'asc')
                ->get();

            return ResponseFormatter::success($datas, 'Berhasil show data');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Gagal show data', 500);
        }
    }

    public function searchData(Request $request)
    {
        try {
            $title = $request->input('title');

            $datas = VoiceChannel::where('status', 'aktif')
                ->where('nama_channel', 'like', '%' . $title . '%')
                ->orderBy('mulai_channel', 'asc')
                ->get();

            return ResponseFormatter::success([
                "datas" => $datas,
                "total_data" => $datas->count()
            ], 'Berhasil show data');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Gagal show data', 500);
        }
    }

    public function cekTransaksiVoice(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found or token expired'
                ], 'Unauthorized', 401);
            }

            $datas = DB::table('trs_voice_anggota')
            ->leftJoin('tabel_channel_voice as B', 'trs_voice_anggota.id_channel_voice', '=', 'B.id_channel_voice')
            ->select(
                'B.kode_channel',
                'B.nama_channel',
                'trs_voice_anggota.id_channel_voice',
                'trs_voice_anggota.absen',
                'trs_voice_anggota.status',
                'trs_voice_anggota.counter'
            )
            ->where('trs_voice_anggota.id_users')
            ->where('trs_voice_anggota.absen', 1)
            ->where('trs_voice_anggota.status', 'aktif')
            ->orderBy('trs_voice_anggota.created', 'desc')
            ->limit(1)
            ->first();

            return ResponseFormatter::success([
                "datas" => $datas,
                "total_data" => TrsVoiceAnggota::where('id_users', $user->id)->where('absen', '1')->count()
            ], 'Berhasil show data');

        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Gagal show data', 500);
        }
    }


    public function insertTrsVoice(Request $request)
    {
        try {
            date_default_timezone_set("Asia/Jakarta");

            $user = JWTAuth::parseToken()->authenticate();
            $id_users = $user->id;

            $request->validate([
                'id_travel' => 'required|integer',
                'absen' => 'required'
            ]);

            $times = Carbon::now()->format('Y-m-d H:i:s');

            $datas_count = TrsVoiceAnggota::where('id_users', $id_users)
                ->where('id_channel_voice', $request->id_channel_voice)
                ->count();

             if ($datas_count > 0) {
                $datas_cek = TrsVoiceAnggota::where('id_users', $id_users)
                    ->where('id_channel_voice', $request->id_channel_voice)
                    ->first();

                $datas_cek->counter += 1;
                $datas_cek->absen = '1';
                $datas_cek->status = 'aktif';
                // $datas_cek->updated = $times;
                $datas_cek->save();

                return ResponseFormatter::success($datas_cek, 'Berhasil Update');
            } else {
                $voice = new TrsVoiceAnggota();
                $voice->id_channel_voice = $request->id_channel_voice;
                $voice->id_travel = $request->id_travel;
                $voice->id_users = $id_users;
                $voice->id_paket = $request->id_paket ?? null;
                $voice->absen = $request->absen;
                $voice->author = strtolower($user->email);
                $voice->updater = strtolower($user->email);
                $voice->save();

                return ResponseFormatter::success($voice, 'Berhasil di Input');
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Insert Failed', 500);
        }
    }
    
    public function updateTrsVoice(Request $request)
    {
        try {
            date_default_timezone_set("Asia/Bangkok");

            $user = JWTAuth::parseToken()->authenticate();
            $id_users = $user->id;

            $datas_cek = TrsVoiceAnggota::where('id_users', $user->id)
                ->where('id_channel_voice', $request->id_channel_voice)
                ->first();
            
            $times = Carbon::now()->format('Y-m-d H:i:s');

            $datas_cek = TrsVoiceAnggota::where('id_users', $id_users)
            ->where('id_channel_voice', $request->id_channel_voice)
            ->first();

            if ($datas_cek) {
                if (is_null($datas_cek->created)) {
                    $datas_cek->created = $times;
                }

                $datas_cek->absen = $request->absen ?? $datas_cek->absen;
                $datas_cek->status = $request->status ?? $datas_cek->status;
                $datas_cek->counter += 1;
                $datas_cek->updated = $times;
                $datas_cek->save();

                return ResponseFormatter::success($datas_cek, 'Berhasil Update');
            } else {
                return ResponseFormatter::error([
                    'message' => 'Data tidak ditemukan'
                ], 'Data Not Found', 404);
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Authenticated Failed', 500);
        }
    }

    public function insertLogVoiceOnline(Request $request)
    {
        try {
            date_default_timezone_set("Asia/Bangkok");

            $user = JWTAuth::parseToken()->authenticate();
            
            $datas_cek = TrsVoiceOnline::where('id_users', $user->id)
                ->where('id_channel_voice', $request->id_channel_voice)
                ->first();
            
            if ($datas_cek) {
                $datas_cek->counter += 1;
                $datas_cek->absen = '1';
                $datas_cek->status = 'aktif';
                $datas_cek->created = date('Y-m-d H:i:s');
                // $datas_cek->updated = date('Y-m-d H:i:s');
                $datas_cek->save();
                
                return ResponseFormatter::success($datas_cek, 'Berhasil di Input');
            } else {
                $voice = TrsVoiceOnline::create([
                    'id_channel_voice' => $request->id_channel_voice,
                    'kode_agora' => $request->kode_agora,
                    'id_users' => $user->id,
                    'absen' => $request->absen,
                    'author' => strtolower($user->email),
                    'updater' => strtolower($user->email),
                    'created' => date('Y-m-d H:i:s'),
                    // 'updated' => date('Y-m-d H:i:s')
                ]);
                
                return ResponseFormatter::success($voice, 'Berhasil di Input');
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Authenticated Failed', 500);
        }
    }

    public function updateLogVoiceOnline(Request $request)
    {
        try {
            date_default_timezone_set("Asia/Bangkok");

            $user = JWTAuth::parseToken()->authenticate();
            $id_users = $user->id;

            $request->validate([
                'status' => 'required|string'
            ]);

            $times = Carbon::now()->format('Y-m-d H:i:s');

            $trsVoice = TrsVoiceOnline::where('id_users', $user->id)
                ->where('id_channel_voice', $request->id_channel_voice)
                ->first();

            if ($trsVoice) {
                if (is_null($trsVoice->created)) {
                    $trsVoice->created = $times;
                }
                $trsVoice->absen = $request->absen ?? $trsVoice->absen;
                $trsVoice->status = $request->status;
                $trsVoice->counter += 1;
                $trsVoice->updated = $times;
                $trsVoice->save();
    
                return ResponseFormatter::success($trsVoice, 'Berhasil Update');
            }
            return ResponseFormatter::error([
                'message' => 'Data tidak ditemukan'
            ], 'Update Gagal', 404);
    

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Update Gagal', 500);
        }
    }

    public function getRealtimeUserOnline(Request $request, $id_channel_voice)
    {
        try {
            $datas = TrsVoiceOnline::where('id_channel_voice', $id_channel_voice)
                ->where('absen', '1')
                ->count();
            
            return ResponseFormatter::success($datas, 'Berhasil show data');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Gagal show data', 500);
        }
    }
    
}
