<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsVoiceOnline extends Model
{
    use HasFactory;

    public $table = "trs_voice_online";
    public $timestamps = false;

    protected $fillable = [
        'kode_agora', 'id_channel_voice', 'id_users', 'absen', 'counter', 'status', 'author', 'updater'
    ];
    
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    public $primaryKey = "id_online";
}
