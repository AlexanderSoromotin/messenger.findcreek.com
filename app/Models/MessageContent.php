<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageContent extends Model
{
    use HasFactory;

    protected $guarded = [];
//    protected $guarded = ['id', 'message_id', 'created_at', 'updated_at'];

    protected $casts = [
        'attachments_ids' => 'json',
    ];
}
