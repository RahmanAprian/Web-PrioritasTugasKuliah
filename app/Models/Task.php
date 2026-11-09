<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['course_id','title','description','deadline','grade_contribution','difficulty','status','auto_score','auto_priority','manual_priority'];
    protected function casts(): array { return ['deadline' => 'datetime', 'grade_contribution' => 'decimal:2', 'auto_score' => 'decimal:4']; }
    public function user() { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
}
