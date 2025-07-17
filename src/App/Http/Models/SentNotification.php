<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentNotification extends Model
{
    protected $table = 'notifications';
    protected $fillable = ['data'];
}