<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileModel extends Model
{
    // Defina a tabela caso o nome não siga o padrão plural
    protected $table = 'files';

    // Defina os campos que são atribuíveis em massa
    protected $fillable = [
        'message_id',
        'file_id',
        'file_sha256',
        'file_url',
        'file_src',
        'file_size',
        'file_mime_type'
    ];


    // Defina o relacionamento com a tabela messages
    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
