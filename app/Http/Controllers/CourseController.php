<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = $request->user()->courses()->withCount('tasks')->latest()->get();
        return view('courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $d = $request->validate(['name'=>'required|string|max:120','code'=>'nullable|string|max:30','lecturer'=>'nullable|string|max:120']);
        $request->user()->courses()->create($d);
        return back()->with('success','Mata kuliah ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $course = $request->user()->courses()->findOrFail($id);
        $course->update($request->validate(['name'=>'required|string|max:120','code'=>'nullable|string|max:30','lecturer'=>'nullable|string|max:120']));
        return back()->with('success','Mata kuliah diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $request->user()->courses()->findOrFail($id)->delete();
        return back()->with('success','Mata kuliah beserta tugas terkait dihapus.');
    }
}
