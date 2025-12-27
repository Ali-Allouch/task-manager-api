<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $status = $request->query('status', 'all');
        $cacheKey = "user_{$userId}_tasks_{$status}";

        return Cache::remember($cacheKey, 3600, function () use ($request) {
            $query = $request->user()->tasks();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            Log::info("--- I am fetching from the DATABASE now! ---");
            return $request->user()->tasks()->latest()->get();
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('tasks', config('filesystems.default'));
            $data['attachment'] = $path;
        }

        $task = $request->user()->tasks()->create($data);

        Cache::forget("user_" . $request->user()->id . "_tasks_all");
        Cache::forget("user_" . $request->user()->id . "_tasks_pending");
        Cache::forget("user_" . $request->user()->id . "_tasks_in_progress");
        Cache::forget("user_" . $request->user()->id . "_tasks_completed");

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:pending,in_progress,completed',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('attachment')) {
            if ($task->attachment) {
                Storage::disk(config('filesystems.default'))->delete($task->attachment);
            }

            $path = $request->file('attachment')->store('tasks', config('filesystems.default'));
            $data['attachment'] = $path;
        }

        $task->update($data);

        Cache::forget("user_" . $request->user()->id . "_tasks_all");
        Cache::forget("user_" . $request->user()->id . "_tasks_pending");
        Cache::forget("user_" . $request->user()->id . "_tasks_in_progress");
        Cache::forget("user_" . $request->user()->id . "_tasks_completed");

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->delete();

        Cache::forget("user_" . $request->user()->id . "_tasks_all");
        Cache::forget("user_" . $request->user()->id . "_tasks_pending");
        Cache::forget("user_" . $request->user()->id . "_tasks_in_progress");
        Cache::forget("user_" . $request->user()->id . "_tasks_completed");

        return response()->json(['message' => 'Task deleted successfully (Soft Deleted)']);
    }
}
