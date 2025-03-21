<?php

namespace App\Jobs;

use App\Events\GotAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JoinChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $voice_channel_id;
    public array $audience;

    /**
     * Create a new job instance.
     */
    public function __construct(int $voice_channel_id, array $audience)
    {
        $this->voice_channel_id = $voice_channel_id;
        $this->audience = $audience;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Broadcast event GotAudience dengan data user dalam channel
        broadcast(new GotAudience($this->voice_channel_id, $this->audience));
    }
}
