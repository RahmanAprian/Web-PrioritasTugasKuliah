<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,DashboardController,CourseController,TaskController,PrioritySettingController,DemoController};
use App\Http\Middleware\EnsureActiveDemoWorkspace;
Route::get('/',fn()=>auth()->check()?redirect()->route('dashboard'):redirect()->route('login'));
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/demo/start',[DemoController::class,'start'])->name('demo.start');
Route::post('/demo/start',[DemoController::class,'enter'])->name('demo.enter');
Route::middleware(EnsureActiveDemoWorkspace::class)->prefix('demo')->name('demo.')->group(function(){
 Route::get('/',[DemoController::class,'dashboard'])->name('dashboard');
 Route::post('/exit',[DemoController::class,'exit'])->withoutMiddleware(EnsureActiveDemoWorkspace::class)->name('exit');
 Route::get('/courses',[DemoController::class,'courses'])->name('courses.index');
 Route::post('/courses',[DemoController::class,'courseStore'])->name('courses.store');
 Route::put('/courses/{course}',[DemoController::class,'courseUpdate'])->name('courses.update');
 Route::delete('/courses/{course}',[DemoController::class,'courseDelete'])->name('courses.destroy');
 Route::get('/tasks',[DemoController::class,'tasks'])->name('tasks.index');
 Route::get('/tasks/create',[DemoController::class,'taskForm'])->name('tasks.create');
 Route::post('/tasks',[DemoController::class,'taskStore'])->name('tasks.store');
 Route::get('/tasks/{task}/edit',[DemoController::class,'taskForm'])->name('tasks.edit');
 Route::put('/tasks/{task}',[DemoController::class,'taskUpdate'])->name('tasks.update');
 Route::delete('/tasks/{task}',[DemoController::class,'taskDelete'])->name('tasks.destroy');
 Route::patch('/tasks/{task}/status',[DemoController::class,'taskStatus'])->name('tasks.status');
 Route::patch('/tasks/{task}/priority',[DemoController::class,'taskPriority'])->name('tasks.priority');
 Route::put('/settings/priority',[DemoController::class,'settings'])->name('settings.priority');
});
Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'login'])->name('login');Route::post('/login',[AuthController::class,'authenticate']);Route::get('/register',[AuthController::class,'register'])->name('register');Route::post('/register',[AuthController::class,'storeUser']);});
Route::middleware('auth')->group(function(){Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');Route::get('/courses',[CourseController::class,'index'])->name('courses.index');Route::get('/tasks',[TaskController::class,'index'])->name('tasks.index');Route::get('/tasks/create',[TaskController::class,'create'])->name('tasks.create');Route::post('/tasks',[TaskController::class,'store'])->name('tasks.store');Route::get('/tasks/{task}/edit',[TaskController::class,'edit'])->name('tasks.edit');Route::put('/tasks/{task}',[TaskController::class,'update'])->name('tasks.update');Route::delete('/tasks/{task}',[TaskController::class,'destroy'])->name('tasks.destroy');Route::patch('/tasks/{task}/status',[TaskController::class,'status'])->name('tasks.status');Route::patch('/tasks/{task}/priority',[TaskController::class,'priority'])->name('tasks.priority');Route::post('/courses',[CourseController::class,'store'])->name('courses.store');Route::put('/courses/{course}',[CourseController::class,'update'])->name('courses.update');Route::delete('/courses/{course}',[CourseController::class,'destroy'])->name('courses.destroy');Route::put('/settings/priority',[PrioritySettingController::class,'update'])->name('settings.priority');});
