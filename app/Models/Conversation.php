<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['from', 'contact_name', 'updated_at'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
