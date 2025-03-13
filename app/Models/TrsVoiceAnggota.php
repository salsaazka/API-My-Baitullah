<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsVoiceAnggota extends Model
{
    use HasFactory;

    public $table = "trs_voice_anggota";
    public $timestamps = false;

    protected $fillable = [
        'id_channel_voice', 'id_travel', 'id_paket', 'id_users', 'absen', 'counter', 'status', 'author', 'updater'
    ];
    
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    public $primaryKey = "id";
}
