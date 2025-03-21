<?php

namespace App\Http\Controllers\Api;

use App\Models\VoiceChannelV2;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\JoinChannel;
use App\Models\UsersVoiceChannel;
use Illuminate\Support\Facades\Auth;

class VoiceChannelController extends Controller
{
    public function index()
    {
        return response()->json(VoiceChannelV2::with('host', 'users')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_channel' => 'required|string|max:255',
            'passcode' => 'nullable|string|max:255',
            'maks_pengguna' => 'required|integer|min:1',
            'is_online' => 'boolean',
        ]);

        $user = Auth::user();

        if (!$user || !$user->tipe_akun) {
            return response()->json([
                'message' => 'Only ustadz can create a voice channel.'
            ], 403);
        }

        $channel = VoiceChannelV2::create([
            'host_id'   => $user->id,
            'nama_channel'      => $request->nama_channel,
            'passcode'  => $request->passcode,
            'maks_pengguna' => $request->maks_pengguna,
            'is_online' => $request->is_online ?? true,
        ]);

        return response()->json($channel, 201);
    }

    public function show(VoiceChannelV2 $voiceChannel)
    {
        $user = Auth::user();

        // Pastikan hanya host yang bisa melihat detailnya
        if ($voiceChannel->host_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($voiceChannel->load('host', 'users'));
    }

    public function update(Request $request, VoiceChannelV2 $voiceChannel)
    {
        $user = Auth::user();

        // Pastikan hanya host yang bisa update channel ini
        if ($voiceChannel->host_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'nama_channel' => 'string|max:255',
            'passcode' => 'nullable|string|max:255',
            'maks_pengguna' => 'integer|min:1',
            'is_online' => 'boolean',
        ]);

        $voiceChannel->update($request->all());

        return response()->json($voiceChannel);
    }

    public function destroy(VoiceChannelV2 $voiceChannel)
    {
        $user = Auth::user();

        // Pastikan hanya host yang bisa menghapus channel ini
        if ($voiceChannel->host_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $voiceChannel->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function joinChannel(Request $request, $channelId)
    {
        $user = Auth::user();
        $channel = VoiceChannelV2::findOrFail($channelId);

        // Jika user adalah host channel ini, aktifkan channel
        if ($channel->host_id === $user->id) {
            if (!$channel->is_online) {
                $channel->update(['is_online' => true]);
                $channel->users()->syncWithoutDetaching([$user->id]);
                 // Ambil daftar user dalam channel
                    $audience = $channel->users()->get()->toArray();

                    // Dispatch job untuk broadcast event GotAudience
                    JoinChannel::dispatch($channelId, $audience);
                return response()->json(['message' => 'Channel activated successfully.']);
            }

            return response()->json(['message' => 'You are already the host of this channel.']);
        }

        // Cek apakah user atau host sudah berada di channel lain
        $currentChannel = UsersVoiceChannel::where('user_id', $user->id)->first();

        if ($currentChannel) {
            if ($currentChannel->voice_channel_id == $channelId) {
                return response()->json([
                    'message' => 'You are already in this channel.'
                ], 403);
            }
            // User sedang berada di channel lain
            return response()->json([
                'message' => 'You must leave the current channel before joining a new one.'
            ], 403);
        }


        // Validasi passcode untuk non-host users
        $request->validate([
            'passcode' => 'required|string'
        ]);

        // Cek apakah channel masih aktif
        if (!$channel->is_online) {
            return response()->json(['message' => 'This channel is not active.'], 403);
        }

        // Cek apakah passcode cocok
        if ($channel->passcode !== $request->passcode) {
            return response()->json(['message' => 'Incorrect passcode.'], 403);
        }

        // Cek apakah channel sudah penuh
        if ($channel->users()->count() >= $channel->maks_pengguna) {
            return response()->json(['message' => 'The channel is full.'], 403);
        }

        // Tambahkan user ke channel
        $channel->users()->syncWithoutDetaching([$user->id]);

        // Ambil daftar user dalam channel
        $audience = $channel->users()->get()->toArray();

        // Dispatch job untuk broadcast event GotAudience
        JoinChannel::dispatch($channelId, $audience);

        return response()->json(['message' => 'Joined the channel successfully.']);
    }

    public function leaveChannel($channelId)
    {
        $user = Auth::user();
        $channel = VoiceChannelV2::findOrFail($channelId);

        // Jika user adalah host
        if ($channel->host_id === $user->id) {
            // Nonaktifkan channel
            $channel->update(['is_online' => false]);

            // Hapus semua user dari channel
            $channel->users()->detach();

            return response()->json(['message' => 'Channel deactivated and all users have been removed.']);
        }

        // Cek apakah user benar-benar ada di dalam channel ini
        if (!$channel->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this channel.'], 400);
        }

        // Hapus user dari channel
        $channel->users()->detach($user->id);

        return response()->json(['message' => 'You have left the channel.']);
    }
}
