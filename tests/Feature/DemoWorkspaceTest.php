<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_entry_is_available_without_registration_and_domain_data_is_not_persisted(): void
    {
        $response = $this->post('/demo/start');
        $response->assertRedirect('/demo');
        $this->get('/demo')->assertOk()->assertSee('MODE UJI')->assertSee('Tugas Contoh');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('priority_settings', 0);
    }

    public function test_demo_workspace_supports_temporary_course_and_task_changes_without_database_writes(): void
    {
        $this->post('/demo/start')->assertRedirect('/demo');
        $this->post('/demo/courses', ['name' => 'Matkul Uji', 'code' => 'UJI01', 'lecturer' => 'Dosen'])->assertRedirect('/demo/courses');
        $this->get('/demo/courses')->assertOk()->assertSee('Matkul Uji');
        $this->post('/demo/tasks', [
            'title' => 'Tugas Uji Baru', 'course_id' => 'demo-course-uji01',
            'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
            'grade_contribution' => 30, 'difficulty' => 'Sulit', 'status' => 'Belum dikerjakan',
        ])->assertRedirect('/demo/tasks');
        $this->get('/demo/tasks')->assertOk()->assertSee('Tugas Uji Baru');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('priority_settings', 0);
    }

    public function test_demo_exit_revokes_access_immediately_and_new_entry_starts_fresh(): void
    {
        $this->travelTo(now());
        $this->post('/demo/start');
        $this->post('/demo/tasks', [
            'title' => 'Temporary', 'course_id' => 'demo-course-web',
            'grade_contribution' => 10, 'difficulty' => 'Mudah', 'status' => 'Belum dikerjakan',
        ])->assertRedirect('/demo/tasks');
        $this->post('/demo/exit')->assertRedirect('/login');
        $this->get('/demo/tasks')->assertRedirect('/demo/start');
        $this->travel(29)->seconds();
        $this->get('/demo/start')->assertOk();
        $this->post('/demo/start')->assertRedirect('/demo');
        $this->get('/demo/tasks')->assertOk()->assertDontSee('Temporary');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_demo_routes_never_authenticate_or_unlock_real_user_routes(): void
    {
        $this->post('/demo/start');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/courses')->assertRedirect('/login');
    }
}
