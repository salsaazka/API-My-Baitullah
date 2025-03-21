<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceChannelV2 extends Model
{
    use HasFactory;

    public $table = "voice_channels_v2";

    protected $fillable = [
        'host_id', // diambdari table users
        'nama_channel',
        'kode_channel',
        'maks_pengguna',
        'is_online',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_voice_channels', 'voice_channel_id', 'user_id');
    }
}
