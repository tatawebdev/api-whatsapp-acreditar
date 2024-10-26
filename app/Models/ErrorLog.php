<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    // Define a tabela associada
    protected $table = 'error_logs';

    // Defina os atributos que são atribuíveis em massa
    protected $fillable = [
        'message',
        'stack_trace',
        'file',
        'line',
    ];
}
