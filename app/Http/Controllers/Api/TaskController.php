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
    /**
     * @OA\Get(
     * path="/api/tasks",
     * summary="Get list of tasks with filtering",
     * tags={"Tasks"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="status",
     * in="query",
     * description="Filter tasks by status",
     * required=false,
     * @OA\Schema(type="string", enum={"pending", "in_progress", "completed"})
     * ),
     * @OA\Response(
     * response=200,
     * description="Tasks retrieved successfully"
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated"
     * )
     * )
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
    /**
     * @OA\Post(
     * path="/api/tasks",
     * summary="Create a new task",
     * tags={"Tasks"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * required={"title", "status"},
     * @OA\Property(property="title", type="string", example="Finish Project"),
     * @OA\Property(property="description", type="string", example="Complete the API documentation"),
     * @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed"}),
     * @OA\Property(property="attachment", type="string", format="binary")
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Task created successfully"
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation error"
     * )
     * )
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
    /**
     * @OA\Get(
     * path="/api/tasks/{id}",
     * summary="Get specific task details",
     * tags={"Tasks"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Task ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Task details retrieved successfully"
     * ),
     * @OA\Response(
     * response=404,
     * description="Task not found"
     * )
     * )
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
    /**
     * @OA\Post(
     * path="/api/tasks/{id}",
     * summary="Update an existing task",
     * description="Use form-data with _method=PUT to support file uploads",
     * tags={"Tasks"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Task ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="_method", type="string", example="PUT"),
     * @OA\Property(property="title", type="string", example="Updated Title"),
     * @OA\Property(property="description", type="string", example="Updated Description"),
     * @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed"}),
     * @OA\Property(property="attachment", type="string", format="binary")
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Task updated successfully"
     * )
     * )
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
    /**
     * @OA\Delete(
     * path="/api/tasks/{id}",
     * summary="Delete a task",
     * tags={"Tasks"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Task ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Task deleted successfully"
     * )
     * )
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
