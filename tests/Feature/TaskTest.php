<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Task;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_task_requires_a_title()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'status' => 'pending'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    #[Test]
    public function task_status_must_be_one_of_the_allowed_values()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'New Task',
                'status' => 'finished' 
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function attachment_must_be_a_valid_file_type()
    {
        Storage::fake(config('filesystems.default'));
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'Task with invalid file',
                'status' => 'pending',
                'attachment' => $file
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachment']);
    }

    #[Test]
    public function a_user_can_create_a_task_with_an_attachment()
    {
        Storage::fake(config('filesystems.default'));

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('task_image.jpg');
        $data = [
            'title' => 'Test Task from Automated Test',
            'status' => 'pending',
            'attachment' => $file,
        ];

        $response = $this->postJson('/api/tasks', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task from Automated Test',
            'user_id' => $user->id,
        ]);

        Storage::assertExists('tasks/' . $file->hashName());
    }

    #[Test]
    public function a_user_cannot_delete_another_users_task()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherUser);
        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }
}
