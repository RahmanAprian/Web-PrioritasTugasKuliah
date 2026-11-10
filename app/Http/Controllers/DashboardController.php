<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Task,Course,PrioritySetting};
use App\Services\PriorityCalculator;
class DashboardController extends Controller
{
 public function index(Request $r, PriorityCalculator $calc){$u=$r->user();$s=PrioritySetting::firstOrCreate(['user_id'=>$u->id],['urgency_weight'=>0.5,'contribution_weight'=>0.3,'difficulty_weight'=>0.2,'horizon_days'=>14]);$all=$u->tasks()->where('status','!=','Selesai')->with('course')->get();foreach($all as $t){$score=$calc->score($t->deadline?->toDateString(),(float)$t->grade_contribution,$t->difficulty,$s);$t->auto_score=$score;$t->auto_priority=$calc->category($score);$t->auto_reason=$calc->reason($t->deadline?->toDateString(),(float)$t->grade_contribution,$t->difficulty,$s);if((float)$t->getRawOriginal('auto_score')!==$score||$t->getRawOriginal('auto_priority')!==$t->auto_priority)$t->forceFill(['auto_score'=>$score,'auto_priority'=>$t->auto_priority])->save();}$tasks=$all->filter(fn($t)=>!$r->filled('course')||$t->course_id===$r->integer('course'))->filter(fn($t)=>!$r->filled('status')||$t->status===$r->string('status'))->sortByDesc(fn($t)=>$t->manual_priority?10+['Rendah'=>1,'Sedang'=>2,'Tinggi'=>3][$t->manual_priority]:($t->auto_score??-1))->values();$courses=$u->courses()->withCount('tasks')->get();return view('dashboard',compact('tasks','courses','all','s'));}
}
