<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

Broadcast::channel('voice-channel.{voice_channel_id}', function ($user, $voice_channel_id) {
    return $user->channels()->where('voice_channels.id', $voice_channel_id)->exists();
});