<?php
namespace Tests\Unit;
use App\Models\PrioritySetting;
use App\Services\PriorityCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
class PriorityCalculatorTest extends TestCase {
 protected function setUp():void{parent::setUp();Carbon::setTestNow('2026-11-09 12:00:00');}
 protected function tearDown():void{Carbon::setTestNow();parent::tearDown();}
 private function settings():PrioritySetting{$s=new PrioritySetting(['urgency_weight'=>.5,'contribution_weight'=>.3,'difficulty_weight'=>.2,'horizon_days'=>14]);return $s;}
 public function test_research_acceptance_cases():void{$c=new PriorityCalculator();$s=$this->settings();$this->assertSame(0.9643,$c->score('2026-11-10',30,'Sulit',$s));$this->assertSame('Tinggi',$c->category(.9643));$this->assertSame(.5833,$c->score('2026-11-16',15,'Sedang',$s));$this->assertSame('Sedang',$c->category(.5833));$this->assertSame(.1667,$c->score('2026-11-23',10,'Mudah',$s));$this->assertSame('Rendah',$c->category(.1667));$this->assertSame(.8333,$c->score('2026-11-07',15,'Sedang',$s));$this->assertSame('Tinggi',$c->category(.8333));$this->assertNull($c->score(null,30,'Sulit',$s));}
 public function test_reason_matches_dominant_weighted_factor_and_handles_missing_deadline():void{$c=new PriorityCalculator();$s=$this->settings();$this->assertSame('Tenggat relatif dekat; urgensi paling besar memengaruhi rekomendasi.',$c->reason('2026-11-10',30,'Sulit',$s));$this->assertSame('Tenggat belum ditentukan, jadi tugas belum masuk rekomendasi otomatis.',$c->reason(null,30,'Sulit',$s));$s->urgency_weight=0.1;$s->contribution_weight=0.8;$s->difficulty_weight=0.1;$this->assertSame('Kontribusi nilai paling besar memengaruhi rekomendasi.',$c->reason('2026-11-16',30,'Mudah',$s));}
}
