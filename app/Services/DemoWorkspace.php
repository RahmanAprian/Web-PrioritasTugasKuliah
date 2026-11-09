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
                ['id' => 'demo-task-1', 'course_id' => 'demo-course-web', 'title' => 'Tugas Contoh — Proyek Website', 'description' => 'Contoh data mode uji.', 'deadline' => now()->addDay()->toDateTimeString(), 'grade_contribution' => 30, 'difficulty' => 'Sulit', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-2', 'course_id' => 'demo-course-db', 'title' => 'Normalisasi Basis Data', 'description' => 'Contoh data mode uji.', 'deadline' => now()->addDays(7)->toDateTimeString(), 'grade_contribution' => 15, 'difficulty' => 'Sedang', 'status' => 'Sedang dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-3', 'course_id' => 'demo-course-dm', 'title' => 'Laporan Eksplorasi Data', 'description' => 'Contoh data mode uji.', 'deadline' => now()->addDays(14)->toDateTimeString(), 'grade_contribution' => 10, 'difficulty' => 'Mudah', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
                ['id' => 'demo-task-4', 'course_id' => 'demo-course-web', 'title' => 'Baca dokumentasi framework', 'description' => 'Contoh tugas tanpa deadline.', 'deadline' => null, 'grade_contribution' => 5, 'difficulty' => 'Mudah', 'status' => 'Belum dikerjakan', 'manual_priority' => null],
            ],
        ];
    }
}
