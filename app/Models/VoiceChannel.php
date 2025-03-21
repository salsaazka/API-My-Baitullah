<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceChannel extends Model
{
    use HasFactory;

    public $table = "tabel_channel_voice";
    // public $timestamps = false;

    protected $fillable = [
        'kode_channel', 'id_travel', 'id_paket', 'nama_channel', 'nama_channel_opsi', 'kepala_group', 'id_users', 'mulai_channel', 'selesai_channel', 'status'
    ];
    
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    public $primaryKey = "id_channel_voice";

}
