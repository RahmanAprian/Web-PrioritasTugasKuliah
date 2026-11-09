<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['name', 'code', 'lecturer'];
    public function user() { return $this->belongsTo(User::class); }
    public function tasks() { return $this->hasMany(Task::class); }
}
