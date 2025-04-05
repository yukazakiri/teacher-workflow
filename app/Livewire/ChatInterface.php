<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Prism\Prism\Enums\Provider;

class ChatInterface extends Component
{
    use WithFileUploads;

    public ?Chat $currentChat = null;

    public string $message = '';

    public string $model = 'gpt-4-turbo-preview';

    public array $availableModels = [
        'gpt-4-turbo-preview' => 'GPT-4 Turbo',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        'gemini-pro' => 'Gemini Pro',
    ];

    public function mount(?Chat $chat = null)
    {
        $this->currentChat = $chat;
        if ($this->currentChat && ! empty($this->currentChat->model)) {
            $this->model = $this->currentChat->model;
        }

        // Ensure we always have a valid model selected
        if (empty($this->model) || ! array_key_exists($this->model, $this->availableModels)) {
            $this->model = 'gpt-4-turbo-preview';
        }
    }

    public function createNewChat()
    {
        $now = now();
        $defaultTitle = 'New conversation '.$now->format('M d, g:i A');

        $this->currentChat = Chat::create([
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->currentTeam->id,
            'model' => $this->model ?? 'gpt-4-turbo-preview',
            'title' => $defaultTitle,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function switchChat(Chat $chat)
    {
        $this->currentChat = $chat;
        if (! empty($chat->model) && array_key_exists($chat->model, $this->availableModels)) {
            $this->model = $chat->model;
        }
    }

    public function sendMessage()
    {
        if (empty($this->message)) {
            return;
        }

        if (! $this->currentChat) {
            $this->createNewChat();
        }

        $firstMessage = $this->currentChat->messages()->count() === 0;
        $userMessage = $this->message;

        // Create user message
        $this->currentChat->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Get AI response
        $response = $this->getAIResponse();

        // Create AI message
        $this->currentChat->messages()->create([
            'role' => 'assistant',
            'content' => $response,
        ]);

        // If this is the first message exchange, generate a better title
        if ($firstMessage) {
            $this->generateChatTitle($userMessage);
        }

        $this->message = '';

        // Dispatch event for scrolling
        $this->dispatch('message-sent');
    }

    protected function generateChatTitle(string $firstMessage): void
    {
        // Create a title based on the first message
        $title = Str::limit($firstMessage, 40);

        // Update the chat title
        $this->currentChat->update([
            'title' => $title,
        ]);
    }

    protected function getAIResponse(): string
    {
        $messages = $this->currentChat->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->toArray();

        $providerType = str_starts_with($this->model, 'gpt') ? Provider::OpenAI : Provider::Gemini;

        try {
            $response = app('prism')
                ->resolve($providerType)
                ->chat([
                    'model' => $this->model,
                    'messages' => $messages,
                ]);

            return $response->content ?? 'No response generated. Please try again.';
        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    public function render()
    {
        $chats = Chat::where('user_id', Auth::id())
            ->where('team_id', Auth::user()->currentTeam->id)
            ->latest()
            ->get();

        return view('livewire.chat-interface', [
            'chats' => $chats,
        ]);
    }
}
