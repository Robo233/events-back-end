<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', "start_date", "end_date", "start_time", "end_time", "location", "image", "user_id"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function goingToUsers()
    {
        return $this->belongsToMany(User::class, 'event_user')->withTimestamps();
    }
}
