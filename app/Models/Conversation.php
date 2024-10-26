<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['whatsapp_id', 'contact_name'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
