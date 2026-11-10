<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DemoWorkspace
{
    private const SESSION_KEY = 'demo_workspace';

    public function start(Request $request): string
    {
        $id = (string) Str::uuid();
        $request->session()->put(self::SESSION_KEY, $this->fixtures($id));
        return $id;
    }

    public function data(Request $request): ?array
    {
        $data = $request->session()->get(self::SESSION_KEY);
        return is_array($data) && ($data['status'] ?? null) === 'active' ? $data : null;
    }

    public function update(Request $request, array $data): void
    {
        if (!$this->data($request)) abort(419, 'Sesi demo sudah kedaluwarsa.');
        $request->session()->put(self::SESSION_KEY, $data);
    }

    public function exit(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerateToken();
    }

    private function fixtures(string $id): array
    {
        $today = now()->startOfDay();

        return [
            'workspace_id' => $id,
            'status' => 'active',
            'purge_at' => null,
            'settings' => ['urgency_weight' => .5, 'contribution_weight' => .3, 'difficulty_weight' => .2, 'horizon_days' => 14],
            'courses' => [
                ['id' => 'demo-course-web', 'name' => 'Pemrograman Web', 'code' => 'IF-302', 'lecturer' => 'Dosen Contoh'],
                ['id' => 'demo-course-db', 'name' => 'Basis Data', 'code' => 'IF-303', 'lecturer' => 'Dosen Contoh'],
                ['id' => 'demo-course-dm', 'name' => 'Data Mining', 'code' => 'IF-401', 'lecturer' => 'Dosen Contoh'],
            ],
            'tasks' => [
                ['id' => 'demo-task-a5', 'course_id' => 'demo-course-web', 'title' => 'A5 Proyek aplikasi', 'description' => 'Skenario A: tenggat 6 hari; kontribusi nilai 30%; kesulitan Sulit.', 'deadline' => $today->copy()->addDays(6)->toDateString(), 'grade_contribution' => 30, 'difficulty' => 'Sulit', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-a2', 'course_id' => 'demo-course-db', 'title' => 'A2 Laporan praktikum', 'description' => 'Skenario A: tenggat 2 hari; kontribusi nilai 15%; kesulitan Sedang.', 'deadline' => $today->copy()->addDays(2)->toDateString(), 'grade_contribution' => 15, 'difficulty' => 'Sedang', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-a4', 'course_id' => 'demo-course-db', 'title' => 'A4 Makalah analisis', 'description' => 'Skenario A: tenggat 4 hari; kontribusi nilai 20%; kesulitan Sulit.', 'deadline' => $today->copy()->addDays(4)->toDateString(), 'grade_contribution' => 20, 'difficulty' => 'Sulit', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-a1', 'course_id' => 'demo-course-dm', 'title' => 'A1 Kuis harian', 'description' => 'Skenario A: tenggat 1 hari; kontribusi nilai 10%; kesulitan Mudah.', 'deadline' => $today->copy()->addDay()->toDateString(), 'grade_contribution' => 10, 'difficulty' => 'Mudah', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-a3', 'course_id' => 'demo-course-dm', 'title' => 'A3 Resume bab', 'description' => 'Skenario A: tenggat 3 hari; kontribusi nilai 10%; kesulitan Mudah.', 'deadline' => $today->copy()->addDays(3)->toDateString(), 'grade_contribution' => 10, 'difficulty' => 'Mudah', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-a6', 'course_id' => 'demo-course-web', 'title' => 'A6 Presentasi', 'description' => 'Skenario A: tenggat 9 hari; kontribusi nilai 20%; kesulitan Sedang.', 'deadline' => $today->copy()->addDays(9)->toDateString(), 'grade_contribution' => 20, 'difficulty' => 'Sedang', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
            ],
        ];
    }
}
