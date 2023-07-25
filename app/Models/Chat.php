<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_chats', 'chat_id', 'user_id')->withTimestamps();
    }

    public function activeMembers()
    {
        return $this->belongsToMany(User::class, 'user_chats', 'chat_id', 'user_id')
            ->wherePivot('is_active', "true");
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getMessagesWithContent()
    {
        return $this->hasMany(Message::class)->with('content');
    }
}
