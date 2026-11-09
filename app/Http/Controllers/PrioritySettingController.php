<?php
namespace App\Http\Controllers;
use App\Models\PrioritySetting;
use Illuminate\Http\Request;
class PrioritySettingController extends Controller {
 public function update(Request $r){$d=$r->validate(['urgency_weight'=>'required|numeric|min:0|max:1','contribution_weight'=>'required|numeric|min:0|max:1','difficulty_weight'=>'required|numeric|min:0|max:1','horizon_days'=>'required|integer|min:1|max:365']);if(abs($d['urgency_weight']+$d['contribution_weight']+$d['difficulty_weight']-1)>0.0001)return back()->withErrors(['weights'=>'Jumlah bobot harus 1.']);PrioritySetting::updateOrCreate(['user_id'=>$r->user()->id],$d);return back()->with('success','Pengaturan prioritas disimpan.');}
}
