<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Task;
use App\Notifications\CommentAdded;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     * path="/api/tasks/{task}/comments",
     * summary="Get all comments for a specific task",
     * tags={"Comments"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="task",
     * in="path",
     * required=true,
     * description="Task ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="List of comments retrieved successfully"
     * )
     * )
     */
    public function index(Task $task)
    {
        return response()->json($task->comments()->with('user')->latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     * path="/api/tasks/{task}/comments",
     * summary="Add a comment to a specific task",
     * description="Adds a comment and triggers an email notification to the task owner",
     * tags={"Comments"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="task",
     * in="path",
     * required=true,
     * description="ID of the task to comment on",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"content"},
     * @OA\Property(property="content", type="string", example="Great progress on this task!")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Comment added successfully"
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated"
     * ),
     * @OA\Response(
     * response=404,
     * description="Task not found"
     * )
     * )
     */
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'content' => 'required|string|min:2',
        ]);

        $comment = $task->comments()->create([
            'content' => $request->content,
            'user_id' => $request->user()->id,
        ]);

        $comment->load('user');

        $task->user->notify(new CommentAdded($comment));

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     * path="/api/comments/{id}",
     * summary="Get specific comment details",
     * tags={"Comments"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Comment ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Comment details retrieved successfully"
     * ),
     * @OA\Response(
     * response=404,
     * description="Comment not found"
     * )
     * )
     */
    public function show(Comment $comment)
    {
        return response()->json($comment->load(['user', 'task']));
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     * path="/api/comments/{id}",
     * summary="Update an existing comment",
     * tags={"Comments"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Comment ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"content"},
     * @OA\Property(property="content", type="string", example="This is an updated comment content.")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Comment updated successfully"
     * ),
     * @OA\Response(
     * response=403,
     * description="Unauthorized to update this comment"
     * )
     * )
     */
    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['content' => 'required|string|min:2']);
        $comment->update($request->only('content'));

        return response()->json(['message' => 'Comment updated', 'comment' => $comment]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     * path="/api/comments/{id}",
     * summary="Delete a comment",
     * tags={"Comments"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="Comment ID",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Comment deleted successfully"
     * ),
     * @OA\Response(
     * response=403,
     * description="Unauthorized to delete this comment"
     * )
     * )
     */
    public function destroy(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
