<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentAdded;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authenticated_user_can_comment_on_a_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $commentData = ['content' => 'This is a test comment.'];

        $response = $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/comments", $commentData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'This is a test comment.'
        ]);
    }

    #[Test]
    public function a_comment_requires_content()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/tasks/{$task->id}/comments", [
                'content' => ''
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    #[Test]
    public function adding_a_comment_triggers_a_notification()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($commenter)
            ->postJson("/api/tasks/{$task->id}/comments", [
                'content' => 'Notification test comment'
            ]);

        Notification::assertSentTo(
            $owner,
            CommentAdded::class
        );
    }
}
