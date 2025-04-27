<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Team;
use App\Services\PrismChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AiStreamControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'owner']);
        $this->user->switchTeam($this->team);
    }

    public function test_stream_response_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/stream', [
            'model' => 'GPT-4o',
            'style' => 'default',
            'message' => 'Hello, world!'
        ]);

        $response->assertStatus(401);
    }

    public function test_stream_response_with_new_conversation(): void
    {
        // Mock the chat service to avoid actual API calls
        $this->mock(PrismChatService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAvailableModels')
                ->andReturn(['GPT-4o', 'Claude 3 Sonnet']);
                
            $mock->shouldReceive('createConversation')
                ->andReturn(new Conversation([
                    'id' => 1,
                    'user_id' => $this->user->id,
                    'team_id' => $this->team->id,
                    'title' => 'Test Conversation',
                    'model' => 'GPT-4o',
                    'style' => 'default',
                    'last_activity_at' => now(),
                ]));
        });

        $response = $this->actingAs($this->user)
            ->get('/api/ai/stream'); // Just test the connection setup

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
    }

    public function test_fetch_available_models(): void
    {
        // Mock the chat service
        $this->mock(PrismChatService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAvailableModels')
                ->once()
                ->andReturn(['GPT-4o', 'Claude 3 Sonnet', 'Gemini 1.5 Pro']);
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/ai/models');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonFragment(['GPT-4o'])
            ->assertJsonFragment(['Claude 3 Sonnet'])
            ->assertJsonFragment(['Gemini 1.5 Pro']);
    }

    public function test_fetch_recent_chats(): void
    {
        // Create a few conversations for the user
        Conversation::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'last_activity_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/chats/recent');

        $response->assertStatus(200)
            ->assertJsonCount(3);

        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'model',
                'last_activity',
            ]
        ]);
    }

    public function test_fetch_specific_conversation(): void
    {
        // Create a conversation with messages
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'title' => 'Test Conversation',
            'model' => 'GPT-4o',
            'style' => 'default',
            'last_activity_at' => now(),
        ]);

        // Create some messages
        $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Hello',
            'user_id' => $this->user->id,
        ]);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Hi there!',
            'user_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/conversations/{$conversation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $conversation->id,
                'title' => 'Test Conversation',
                'model' => 'GPT-4o',
                'style' => 'default',
            ])
            ->assertJsonCount(2, 'messages');
    }

    public function test_update_conversation_model(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'model' => 'GPT-4o',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/conversations/{$conversation->id}/model", [
                'model' => 'Claude 3 Sonnet'
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'model' => 'Claude 3 Sonnet'
        ]);
    }

    public function test_unauthorized_access_denied(): void
    {
        // Create another user
        $otherUser = User::factory()->create();
        
        // Create a conversation for the other user
        $conversation = Conversation::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Try to access the other user's conversation
        $response = $this->actingAs($this->user)
            ->getJson("/api/conversations/{$conversation->id}");

        $response->assertStatus(403);
    }
}
