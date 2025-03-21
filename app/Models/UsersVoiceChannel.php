<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersVoiceChannel extends Model
{
    protected $fillable = [
        'voice_channel_id',
        'user_id',
    ];

    public function voiceChannel()
    {
        return $this->belongsTo(VoiceChannelV2::class, 'voice_channel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
