<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GotAudience implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $voice_channel_id;
    public array $audience;

    /**
     * Create a new event instance.
     */
    public function __construct(int $voice_channel_id, array $audience)
    {
        $this->voice_channel_id = $voice_channel_id;
        $this->audience = $audience;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("voice-channel.{$this->voice_channel_id}"),
        ];
    }

    /**
     * Get the broadcast data.
     */
    public function broadcastWith(): array
    {
        return [
            'voice_channel_id' => $this->voice_channel_id,
            'audience' => $this->audience
        ];
    }
}
