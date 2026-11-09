<?php

namespace App\Http\Controllers;

use App\Services\DemoWorkspace;
use App\Services\PriorityCalculator;
use App\Models\PrioritySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DemoController extends Controller
{
    public function start() { return view('demo.start'); }

    public function enter(Request $request, DemoWorkspace $workspace)
    {
        $request->session()->regenerate();
        $workspace->start($request);
        return redirect()->route('demo.dashboard');
    }

    public function dashboard(Request $request, DemoWorkspace $workspace, PriorityCalculator $calculator)
    {
        $data = $workspace->data($request) ?? abort(419, 'Sesi demo sudah kedaluwarsa.');
        $settings = new PrioritySetting($data['settings']);
        foreach ($data['tasks'] as &$task) {
            $task['auto_score'] = $calculator->score($task['deadline'], (float)$task['grade_contribution'], $task['difficulty'], $settings);
            $task['auto_priority'] = $task['auto_score'] === null ? null : $calculator->category($task['auto_score']);
        }
        unset($task);
        $workspace->update($request, $data);
        $courses = $data['courses'];
        $tasks = collect($data['tasks'])->where('status', '!=', 'Selesai')->sortByDesc(fn($t) => $t['manual_priority'] ? 10 + ['Rendah'=>1,'Sedang'=>2,'Tinggi'=>3][$t['manual_priority']] : ($t['auto_score'] ?? -1))->values();
        $all = collect($data['tasks'])->where('status', '!=', 'Selesai');
        $s = (object)$data['settings'];
        return view('demo.dashboard', compact('tasks', 'courses', 'all', 's'));
    }

    public function courses(Request $request, DemoWorkspace $workspace)
    {
        $data = $workspace->data($request) ?? abort(419);
        return view('demo.courses.index', ['courses' => $data['courses'], 'tasks' => $data['tasks']]);
    }

    public function courseStore(Request $request, DemoWorkspace $workspace)
    {
        $d = $request->validate(['name'=>'required|string|max:120','code'=>'nullable|string|max:30','lecturer'=>'nullable|string|max:120']);
        $data = $workspace->data($request) ?? abort(419);
        $slug = Str::slug($d['code'] ?: Str::random(12));
        $data['courses'][] = ['id'=>'demo-course-'.$slug] + $d;
        $workspace->update($request, $data);
        return redirect()->route('demo.courses.index')->with('success', 'Mata kuliah demo ditambahkan.');
    }

    public function courseUpdate(Request $request, string $course, DemoWorkspace $workspace)
    {
        $d = $request->validate(['name'=>'required|string|max:120','code'=>'nullable|string|max:30','lecturer'=>'nullable|string|max:120']);
        $data = $workspace->data($request) ?? abort(419);
        $found = false;
        foreach ($data['courses'] as &$c) if ($c['id'] === $course) { $c = array_merge($c, $d); $found = true; }
        unset($c);
        abort_unless($found, 404);
        $workspace->update($request, $data);
        return back()->with('success', 'Mata kuliah demo diperbarui.');
    }

    public function courseDelete(string $course, Request $request, DemoWorkspace $workspace)
    {
        $data = $workspace->data($request) ?? abort(419);
        abort_unless(collect($data['courses'])->contains('id', $course), 404);
        $data['courses'] = array_values(array_filter($data['courses'], fn($c) => $c['id'] !== $course));
        $data['tasks'] = array_values(array_filter($data['tasks'], fn($t) => $t['course_id'] !== $course));
        $workspace->update($request, $data);
        return back()->with('success', 'Mata kuliah dan tugas demo terkait dihapus.');
    }

    public function tasks(Request $request, DemoWorkspace $workspace)
    {
        $data = $workspace->data($request) ?? abort(419);
        $calculator = app(PriorityCalculator::class);
        $settings = new PrioritySetting($data['settings']);
        $tasks = collect($data['tasks'])->where('status', '!=', 'Selesai')->map(function ($task) use ($calculator, $settings, $data) {
            $task['auto_score'] = $calculator->score($task['deadline'] ?? null, (float)$task['grade_contribution'], $task['difficulty'], $settings);
            $task['auto_priority'] = $task['auto_score'] === null ? null : $calculator->category($task['auto_score']);
            $task['course_name'] = collect($data['courses'])->firstWhere('id', $task['course_id'])['name'] ?? 'Mata kuliah dihapus';
            return $task;
        });
        if ($request->filled('course_id')) $tasks = $tasks->where('course_id', $request->input('course_id'));
        if ($request->filled('status')) $tasks = $tasks->where('status', $request->input('status'));
        return view('demo.tasks.index', ['tasks'=>$tasks->values(), 'courses'=>$data['courses']]);
    }

    public function taskForm(Request $request, DemoWorkspace $workspace, ?string $task = null)
    {
        $data = $workspace->data($request) ?? abort(419);
        $record = $task ? collect($data['tasks'])->firstWhere('id', $task) : null;
        if ($task) abort_unless($record, 404);
        return view('demo.tasks.form', ['task'=>$record, 'courses'=>$data['courses']]);
    }

    public function taskStore(Request $request, DemoWorkspace $workspace, PriorityCalculator $calculator)
    {
        return $this->saveTask($request, $workspace, $calculator, null);
    }

    public function taskUpdate(Request $request, string $task, DemoWorkspace $workspace, PriorityCalculator $calculator)
    {
        return $this->saveTask($request, $workspace, $calculator, $task);
    }

    private function saveTask(Request $request, DemoWorkspace $workspace, PriorityCalculator $calculator, ?string $id)
    {
        $d = $request->validate(['title'=>'required|string|max:180','description'=>'nullable|string|max:5000','course_id'=>'required|string','deadline'=>'nullable|date','grade_contribution'=>'required|numeric|min:0|max:100','difficulty'=>'required|in:Mudah,Sedang,Sulit','status'=>'required|in:Belum dikerjakan,Sedang dikerjakan,Selesai','manual_priority'=>'nullable|in:Tinggi,Sedang,Rendah']);
        $data = $workspace->data($request) ?? abort(419);
        abort_unless(collect($data['courses'])->contains('id', $d['course_id']), 404);
        $settings = new PrioritySetting($data['settings']);
        $d['auto_score'] = $calculator->score($d['deadline'] ?? null, (float)$d['grade_contribution'], $d['difficulty'], $settings);
        $d['auto_priority'] = $d['auto_score'] === null ? null : $calculator->category($d['auto_score']);
        $d['manual_priority'] = $d['manual_priority'] ?? null;
        if ($id) {
            $found = false;
            foreach ($data['tasks'] as &$t) if ($t['id'] === $id) { $d['id'] = $id; $t = array_merge($t, $d); $found = true; }
            unset($t); abort_unless($found, 404);
        } else {
            $d['id'] = 'demo-task-'.Str::lower(Str::random(12));
            $data['tasks'][] = $d;
        }
        $workspace->update($request, $data);
        return redirect()->route('demo.tasks.index')->with('success', $id ? 'Tugas demo diperbarui.' : 'Tugas demo ditambahkan.');
    }

    public function taskDelete(string $task, Request $request, DemoWorkspace $workspace)
    {
        $data = $workspace->data($request) ?? abort(419);
        abort_unless(collect($data['tasks'])->contains('id', $task), 404);
        $data['tasks'] = array_values(array_filter($data['tasks'], fn($t) => $t['id'] !== $task));
        $workspace->update($request, $data);
        return back()->with('success', 'Tugas demo dihapus.');
    }

    public function taskStatus(string $task, Request $request, DemoWorkspace $workspace)
    {
        $data = $workspace->data($request) ?? abort(419);
        $status = $request->validate(['status'=>'required|in:Belum dikerjakan,Sedang dikerjakan,Selesai'])['status'];
        $found = false;
        foreach ($data['tasks'] as &$t) if ($t['id'] === $task) { $t['status'] = $status; $found = true; }
        unset($t); abort_unless($found, 404);
        $workspace->update($request, $data);
        return back()->with('success', 'Status demo diperbarui.');
    }

    public function taskPriority(string $task, Request $request, DemoWorkspace $workspace)
    {
        $data = $workspace->data($request) ?? abort(419);
        $priority = $request->validate(['manual_priority'=>'nullable|in:Tinggi,Sedang,Rendah'])['manual_priority'];
        $found = false;
        foreach ($data['tasks'] as &$t) if ($t['id'] === $task) { $t['manual_priority'] = $priority; $found = true; }
        unset($t); abort_unless($found, 404);
        $workspace->update($request, $data);
        return back()->with('success', 'Prioritas demo diperbarui.');
    }

    public function settings(Request $request, DemoWorkspace $workspace)
    {
        $d = $request->validate(['urgency_weight'=>'required|numeric|min:0|max:1','contribution_weight'=>'required|numeric|min:0|max:1','difficulty_weight'=>'required|numeric|min:0|max:1','horizon_days'=>'required|integer|min:1|max:365']);
        if (abs($d['urgency_weight'] + $d['contribution_weight'] + $d['difficulty_weight'] - 1) > .0001) return back()->withErrors(['weights'=>'Jumlah bobot harus 1.']);
        $data = $workspace->data($request) ?? abort(419);
        $data['settings'] = $d;
        $workspace->update($request, $data);
        return back()->with('success','Bobot demo disimpan sementara.');
    }

    public function exit(Request $request, DemoWorkspace $workspace)
    {
        $workspace->exit($request);
        $request->session()->regenerate(true);
        return redirect()->route('login')->with('success', 'Mode uji selesai. Data demo sementara sudah dihapus.');
    }
}
