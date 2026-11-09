<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrioritySetting extends Model
{
    protected $fillable = ['user_id','urgency_weight','contribution_weight','difficulty_weight','horizon_days'];
    protected function casts(): array { return ['urgency_weight'=>'float','contribution_weight'=>'float','difficulty_weight'=>'float']; }
    public function user() { return $this->belongsTo(User::class); }
}
