<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\PrismChatService;
use App\Tools\ActivityTool;
use App\Tools\ClassResourceTool;
use App\Tools\ExamTool;
use App\Tools\ScheduleTool;
use App\Tools\StudentTool;
use App\Tools\TeamInfoTool;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class AiStreamController extends Controller
{
    /**
     * Stream a response from the AI assistant.
     */
    public function streamResponse(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                "message" => "required|string",
                "model" => "required|string",
                "style" => "required|string",
                "conversation_id" => "nullable|integer",
                "history" => "nullable|array", // For regenerating responses
            ]);

            $user = $request->user();

            if (!$user) {
                return response()->json(["error" => "Unauthenticated"], 401);
            }

            $message = $validatedData["message"];
            $modelId = $validatedData["model"];
            $style = $validatedData["style"];
            $conversationId = $validatedData["conversation_id"] ?? null;
            $history = $validatedData["history"] ?? null;

            // Get the user's current team
            $team = $user->currentTeam;

            if (!$team) {
                return response()->json(["error" => "No team selected"], 400);
            }

            // Get or create the conversation
            if ($conversationId) {
                $conversation = Conversation::where("id", $conversationId)
                    ->where("user_id", $user->id) // Security: make sure the conversation belongs to the user
                    ->firstOrFail();
            } else {
                // Create a new conversation
                $title = $this->generateTitle($message);

                $conversation = Conversation::create([
                    "user_id" => $user->id,
                    "team_id" => $team->id,
                    "title" => $title,
                    "model" => $modelId,
                    "style" => $style,
                    "last_activity_at" => now(),
                ]);
            }

            // Update conversation last activity
            $conversation->updateLastActivity();

            // If we're not regenerating (i.e. no history provided), save the user message
            if (!$history) {
            $userMessage = ChatMessage::create([
                "conversation_id" => $conversation->id,
                "user_id" => $user->id,
                "role" => "user",
                "content" => $message,
            ]);
            }

            // Create placeholder for AI response
            $aiMessage = ChatMessage::create([
                "conversation_id" => $conversation->id,
                "user_id" => null,
                "role" => "assistant",
                "content" => "",
                "is_streaming" => true,
            ]);

            // Map the model to provider and model name
            [$provider, $modelName] = $this->mapModelToProviderAndModel($modelId);

            // Stream the response
            return response()->stream(
                function () use (
                    $conversation,
                    $message,
                    $provider,
                    $modelName,
                    $style,
                    $aiMessage,
                    $history
                ): void {
                    $aiResponse = "";

                    try {
                        // Get tools collection
                        $tools = $this->getAvailableTools();
                        
                        // Add debug log for request info
                        Log::info('AI Stream Request', [
                            'message' => $message,
                            'provider' => $provider,
                            'model' => $modelName,
                            'conversation_id' => $conversation->id,
                            'tools_available' => array_map(function($tool) {
                                return get_class($tool);
                            }, $tools)
                        ]);
                        
                        // Determine if we're using a history for regeneration or building from scratch
                        if ($history) {
                            // Build messages from provided history for regeneration
                            $prismMessages = $this->buildPrismMessagesFromHistory(
                                $history,
                                $style,
                                Auth::user(),
                                $conversation->context
                                );
                            } else {
                            // Build messages from conversation history for normal response
                            $prismMessages = $this->buildMessageHistoryForStream($conversation);
                        }

                        // Configure Prism for streaming response
                        $prismRequest = Prism::text()
                            ->using($provider, $modelName)
                            ->withMessages($prismMessages);

                        // Add tools if provider supports them - ENSURE THEY ARE ACTUALLY USED
                        $providerSupportsTools = in_array(strtolower($provider), [
                            'openai',
                            'anthropic',
                            'gemini',
                        ]);
                        
                        if ($providerSupportsTools && !empty($tools)) {
                            $prismRequest = $prismRequest
                                ->withTools($tools)
                                ->withMaxSteps(5);  // Allow multiple tool calls
                                
                            // Log the tools being provided to debug
                            Log::info('Adding tools to Prism request', [
                                'tools_count' => count($tools),
                                'provider_supports_tools' => $providerSupportsTools
                            ]);
                        }

                        // Start streaming
                        $stream = $prismRequest->asStream();
                        $hasToolInteraction = false;
                        
                        // Process the response chunks
                        foreach ($stream as $chunk) {
                            // Handle text chunks
                            if (isset($chunk->text) && !is_null($chunk->text)) {
                                echo $chunk->text;
                                $aiResponse .= $chunk->text;
                                
                                // Update the message in the database periodically to show progress
                                $aiMessage->update(['content' => $aiResponse]);
                                
                                // Flush output for immediate display
                                if (ob_get_level() > 0) ob_flush();
                                    flush();
                            }
                            
                            // Log tool calls and results if debugging is needed
                            if (isset($chunk->toolCalls) && !empty($chunk->toolCalls)) {
                                Log::info('Tool calls during stream:', ['calls' => $chunk->toolCalls]);
                                $hasToolInteraction = true;
                            }
                            
                            if (isset($chunk->toolResults) && !empty($chunk->toolResults)) {
                                Log::info('Tool results during stream:', ['results' => $chunk->toolResults]);
                                $hasToolInteraction = true;
                            }
                        }
                        
                        // If we didn't see any tool interactions, log that too
                        if (!$hasToolInteraction) {
                            Log::warning('No tool interactions were observed during stream', [
                                'message' => $message,
                                'model' => $modelName,
                                'provider' => $provider
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        // Log the error
                        Log::error("AI response streaming error: " . $e->getMessage(), [
                            "exception" => $e,
                            "conversation_id" => $conversation->id,
                            "trace" => $e->getTraceAsString()
                        ]);

                        // Send an error message
                        $errorMessage = "Sorry, I encountered an error: " . $e->getMessage();
                        echo $errorMessage;
                        $aiResponse = $errorMessage;
                    }

                    // Save the final AI response
                    $aiMessage->update([
                        "content" => trim($aiResponse),
                        "is_streaming" => false,
                    ]);

                    // Add conversation_id in a way that the frontend can extract
                    echo "\n<!-- CONVERSATION_DATA:" .
                        json_encode([
                            "conversation_id" => $conversation->id,
                            "message_id" => $aiMessage->id,
                        ]) .
                        "-->";
                },
                200,
                $this->getStreamHeaders()
            );
        } catch (\Exception $e) {
            Log::error("Stream response error: " . $e->getMessage(), [
                "exception" => $e,
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Get headers for Server-Sent Events.
     */
    private function getStreamHeaders(): array
    {
        return [
            "Content-Type" => "text/event-stream",
            "Cache-Control" => "no-cache",
            "Connection" => "keep-alive",
            "X-Accel-Buffering" => "no", // Important for Nginx proxying
        ];
    }

    /**
     * Build the message history for the Prism request from an existing conversation.
     * Includes System Prompt and User/Team Context.
     */
    private function buildMessageHistoryForStream(
        Conversation $conversation
    ): array {
        $user = Auth::user();
        $team = $user?->currentTeam;

        // Build Prism message objects directly
        $prismMessages = $this->buildPrismMessagesFromHistory(
            $conversation
                ->messages()
                ->orderBy("created_at", "asc")
                ->get()
                ->toArray(), // Get all messages as array
            $conversation->style,
            $user,
            $conversation->context
        );

        return $prismMessages;
    }

    /**
     * Build Prism message objects from a raw history array.
     * Includes System Prompt and User/Team Context.
     *
     * @param array $history Array of ['role' => string, 'content' => string]
     * @param string $style The conversation style
     * @param \App\Models\User $user The current user
     * @param string|null $conversationContext Specific context for the conversation
     * @return array Array of Prism Message Value Objects
     */
    private function buildPrismMessagesFromHistory(
        array $history,
        string $style,
        \App\Models\User $user,
        ?string $conversationContext
    ): array {
        $prismMessages = [];
        $team = $user?->currentTeam;

        // --- System Prompt & Context ---
        $userContext = "## User & Team Context\n";
        $userContext .= "- Current User: {$user->name}\n";
        if ($team) {
            $userContext .= "- Current Team: {$team->name}\n";
            $userContext .=
                "- Use the available tools (student_data, activity_data, exam_data, schedule_data, user_team_info, class_resource_data) to get specific details when asked.";
        } else {
            $userContext .= "- No active team context.";
        }

        $systemContent = $this->getSystemPromptForStyle($style); // Fetch base style prompt
        if ($conversationContext) {
            $systemContent = $conversationContext . "\n\n" . $systemContent; // Prepend specific context
        }
        $prismMessages[] = new SystemMessage(
            $systemContent . "\n\n" . $userContext
        );
        // --- End System Prompt & Context ---

        // --- Add History Messages ---
        // Limit history length if necessary (e.g., last 10 messages)
        $messageLimit = 10;
        $startIndex = max(0, count($history) - $messageLimit);

        for ($i = $startIndex; $i < count($history); $i++) {
            $message = $history[$i];
            // Skip empty assistant messages (potentially old placeholders)
            if (
                empty(trim($message["content"])) &&
                $message["role"] === "assistant"
            ) {
                continue;
            }

            switch ($message["role"]) {
                case "user":
                    $prismMessages[] = new UserMessage($message["content"]);
                    break;
                case "assistant":
                    // TODO: Handle tool calls/results if they are stored in the history
                    $prismMessages[] = new AssistantMessage(
                        $message["content"]
                    );
                    break;
                // Ignore 'system' role from history as we construct it above
            }
        }
        // --- End History Messages ---

        return $prismMessages;
    }

    /**
     * Map model identifier to provider and model name.
     * (Copied from PrismChatService for standalone use in controller)
     */
    private function mapModelToProviderAndModel(string $modelIdentifier): array
    {
        // Prioritize registered models via PrismServer
        $prisms = PrismServer::prisms();
        $prism = $prisms->firstWhere("name", $modelIdentifier);

        if ($prism) {
            $provider = $prism["provider"] ?? Provider::OpenAI->value; // Default to OpenAI if provider missing
            $model = $prism["model"] ?? "gpt-4o"; // Default model
            return [$provider, $model];
        }

        // Fallback map
        $providerMap = [
            "GPT-4o" => [Provider::OpenAI->value, "gpt-4o"],
            "GPT-4o Mini" => [Provider::OpenAI->value, "gpt-4o-mini"],
            "GPT-4 Turbo" => [Provider::OpenAI->value, "gpt-4-turbo"],
            "GPT-3.5 Turbo" => [Provider::OpenAI->value, "gpt-3.5-turbo"],
            "Gemini 1.5 Pro" => [
                Provider::Gemini->value,
                "gemini-1.5-pro-latest",
            ],
            "Gemini 1.5 Flash" => [
                Provider::Gemini->value,
                "gemini-1.5-flash-latest",
            ],
            "Gemini Pro" => [Provider::Gemini->value, "gemini-pro"], // Added if needed
            "Claude 3 Opus" => [
                Provider::Anthropic->value,
                "claude-3-opus-20240229",
            ],
            "Claude 3 Sonnet" => [
                Provider::Anthropic->value,
                "claude-3-sonnet-20240229",
            ],
            "Claude 3 Haiku" => [
                Provider::Anthropic->value,
                "claude-3-haiku-20240307",
            ],
            // Add Gemini 2.0 Flash if it's a distinct model ID
            "Gemini 2.0 Flash" => [Provider::Gemini->value, "gemini-2.0-flash"], // Assuming this is the ID
        ];

        foreach ($providerMap as $name => $details) {
            // Case-insensitive comparison
            if (strcasecmp($modelIdentifier, $name) == 0) {
                return $details;
            }
        }

        Log::warning(
            "Model identifier not found in map. Defaulting to OpenAI GPT-4o.",
            [
                "identifier" => $modelIdentifier,
            ]
        );
        return [Provider::OpenAI->value, "gpt-4o"];
    }

    /**
     * Get system prompt based on style.
     * (Copied from PrismChatService)
     */
    private function getSystemPromptForStyle(string $style): string
    {
        $basePrompts = [
            "default" =>
                "You are a helpful Teacher Assistant. Provide clear and concise responses.",
            "creative" =>
                "You are a creative Teacher Assistant. Think outside the box and provide imaginative responses.",
            "precise" =>
                "You are a precise Teacher Assistant. Focus on accuracy and factual information. Be concise and to the point.",
            "balanced" =>
                "You are a balanced Teacher Assistant. Provide comprehensive yet accessible responses that balance detail with clarity.",
        ];

        // Updated tool instructions reflecting the new specialized tools
        $toolInstructions = <<<PROMPT

You have access to the following tools to retrieve specific user data:
*   `student_data`: Use for questions about students (listing, details, summaries, counts). **ALWAYS USE THIS TOOL WHENEVER USERS ASK ABOUT STUDENTS BY NAME OR ASK TO LIST STUDENTS.**
*   `activity_data`: Use for questions about activities (listing, details, counts).
*   `exam_data`: Use for questions about exams (listing, details, counts).
*   `schedule_data`: Use to fetch the weekly schedule.
*   `user_team_info`: Use for questions about the current user or team settings (like grading system).
*   `class_resource_data`: Use to find class resources/documents (`find_resources`) or get the content of a specific resource by its ID (`get_resource_content`).

IMPORTANT: You MUST use these tools when the user asks questions about their specific data. For example:
- "Who is Emma in my class?" → Use `student_data` tool to search for students
- "How many students do I have?" → Use `student_data` with query_type="count_students"
- "Show me my activities" → Use `activity_data` tool with query_type="list_activities"
- "What's on my schedule?" → Use `schedule_data` tool

Do not guess or make up information - use the tools to get accurate data.

**Using `class_resource_data` for Content:**
If the user's message contains a string formatted EXACTLY like `resource_uuid:SOME_UUID_VALUE` (where SOME_UUID_VALUE is the actual UUID):
1.  You MUST use the `class_resource_data` tool.
2.  Set the tool's `query_type` parameter to the string value `get_resource_content`.
3.  Extract **ONLY** the UUID part (`SOME_UUID_VALUE`) from the `resource_uuid:SOME_UUID_VALUE` string.
4.  Set the tool's `resource_id` parameter to the extracted UUID value (e.g., `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`). **DO NOT** include the `resource_uuid:` prefix in the `resource_id` parameter value you send to the tool.

Example: If the input message contains `resource_uuid:123e4567-e89b-12d3-a456-426614174000\nWhat is this?`, your FIRST action MUST be to call the tool with parameters: `query_type="get_resource_content"` and `resource_id="123e4567-e89b-12d3-a456-426614174000"`. AFTER retrieving the content, THEN address the rest of the user's query ("What is this?") using the retrieved information.
PROMPT;

        return ($basePrompts[$style] ?? $basePrompts["default"]) .
            $toolInstructions;
    }

    /**
     * Get instances of all available data access tools.
     * (Copied from PrismChatService)
     */
    private function getAvailableTools(): array
    {
        try {
            $gradingService = app(GradingService::class); // Resolve GradingService
            $user = Auth::user();
            $team = $user?->currentTeam;
            
            if (!$team) {
                Log::warning('No team context available for tools initialization');
                return [];
            }

            // Create all tool instances and store the current team in a property or pass to constructor if needed
            $tools = [
                new StudentTool($gradingService),
                new ActivityTool(),
                new ExamTool(),
                new ScheduleTool(),
                new TeamInfoTool(),
                new ClassResourceTool(),
            ];
            
            // Log created tools
            Log::info('Created tool instances', [
                'tool_count' => count($tools),
                'team_id' => $team->id,
                'tools' => array_map(function($tool) {
                    return [
                        'class' => get_class($tool),
                        'name' => $tool->name ?? 'unnamed',
                        'description' => $tool->description ?? 'no description'
                    ];
                }, $tools)
            ]);
            
            return $tools;
        } catch (\Exception $e) {
            Log::error('Error initializing tools: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty array if tool initialization fails
            return [];
        }
    }

    /**
     * Generate a title from the first message
     */
    private function generateTitle(string $message): string
    {
        // Use the first 30 characters of the message as the title
        return \Illuminate\Support\Str::limit($message, 30);
    }

    /**
     * Get system instructions based on style
     */
    private function getSystemInstructions(string $style): string
    {
        $instructions = [
            "default" => "You are a helpful AI assistant for teachers.",
            "creative" =>
                "You are a creative AI assistant for teachers. Be imaginative in your responses.",
            "precise" =>
                "You are a precise AI assistant for teachers. Provide clear, concise, and accurate information.",
            "balanced" =>
                "You are a balanced AI assistant for teachers. Provide helpful information in a conversational way.",
        ];

        return $instructions[$style] ?? $instructions["default"];
    }

    /**
     * Format tool results into readable text
     */
    private function formatToolResult(array $data, string $userQuery): string
    {
        // First, check for error responses
        if (isset($data["error"])) {
            return "I encountered an issue: {$data["error"]}";
        }

        // Format based on the data structure
        $response = "";

        // Team info formatting
        if (isset($data["team_info"])) {
            $info = $data["team_info"];
            $response = "Based on your team settings:\n\n";
            $response .= "You are working with the team '{$info["name"]}'.\n";

            if (isset($info["owner"])) {
                $response .= "Team owner: {$info["owner"]}\n";
            }

            if (isset($info["student_count"])) {
                $response .= "Your team has {$info["student_count"]} students, ";
                $response .= "{$info["activity_count"]} activities, and ";
                $response .= "{$info["exam_count"]} exams.\n";
            }

            if (isset($info["grading_system_description"])) {
                $response .= "Grading system: {$info["grading_system_description"]}\n";
            }

            if (
                isset($data["team_members"]) &&
                is_array($data["team_members"])
            ) {
                $response .= "\nTeam members:\n";
                foreach ($data["team_members"] as $member) {
                    $response .= "- {$member["name"]} (Role: {$member["role"]})\n";
                }
            }
        }

        // Student data formatting
        elseif (isset($data["students"]) && is_array($data["students"])) {
            $response = "Here are your students:\n\n";
            foreach ($data["students"] as $i => $student) {
                $response .=
                    $i +
                    1 .
                    ". {$student["name"]} (ID: {$student["student_id"]})";
                if (isset($student["status"])) {
                    $response .= " - Status: {$student["status"]}";
                }
                $response .= "\n";
            }
            if (isset($data["count"])) {
                $response .= "\nTotal: {$data["count"]} students";
            }
        } elseif (
            isset($data["count"]) &&
            isset($data["item_type"]) &&
            $data["item_type"] === "students"
        ) {
            $response = "Student count information: {$data["description"]}";
        } elseif (isset($data["student"])) {
            $student = $data["student"];
            $response = "Student details for {$student["name"]}:\n";
            $response .= "- Student ID: {$student["student_id"]}\n";
            $response .= "- Status: {$student["status"]}\n";
            if (isset($student["email"])) {
                $response .= "- Email: {$student["email"]}\n";
            }
        } elseif (isset($data["summary"])) {
            $response = $data["summary"]; // Student summary is already formatted
        }

        // Activity data formatting
        elseif (isset($data["activities"]) && is_array($data["activities"])) {
            $response = "Here are your activities:\n\n";
            foreach ($data["activities"] as $i => $activity) {
                $response .= $i + 1 . ". {$activity["title"]}";
                if (isset($activity["status"])) {
                    $response .= " - Status: {$activity["status"]}";
                }
                if (isset($activity["due_date"])) {
                    $dueDate = new \DateTime($activity["due_date"]);
                    $response .= " - Due: {$dueDate->format("M j, Y")}";
                }
                $response .= "\n";
            }
            if (isset($data["count"])) {
                $response .= "\nTotal: {$data["count"]} activities";
            }
        } elseif (
            isset($data["count"]) &&
            isset($data["item_type"]) &&
            $data["item_type"] === "activities"
        ) {
            $response = "Activity count information: {$data["description"]}";
        } elseif (isset($data["activity"])) {
            $activity = $data["activity"];
            $response = "Activity details for {$activity["title"]}:\n";
            if (isset($activity["description"])) {
                $response .= "- Description: {$activity["description"]}\n";
            }
            if (isset($activity["total_points"])) {
                $response .= "- Total points: {$activity["total_points"]}\n";
            }
            if (isset($activity["due_date"])) {
                $dueDate = new \DateTime($activity["due_date"]);
                $response .= "- Due date: {$dueDate->format("M j, Y")}\n";
            }
            $response .= "- Status: {$activity["status"]}\n";
        }

        // Exam data formatting
        elseif (isset($data["exams"]) && is_array($data["exams"])) {
            $response = "Here are your exams:\n\n";
            foreach ($data["exams"] as $i => $exam) {
                $response .= $i + 1 . ". {$exam["title"]}";
                if (isset($exam["status"])) {
                    $response .= " - Status: {$exam["status"]}";
                }
                if (isset($exam["total_points"])) {
                    $response .= " - Points: {$exam["total_points"]}";
                }
                $response .= "\n";
            }
            if (isset($data["count"])) {
                $response .= "\nTotal: {$data["count"]} exams";
            }
        } elseif (
            isset($data["count"]) &&
            isset($data["item_type"]) &&
            $data["item_type"] === "exams"
        ) {
            $response = "Exam count information: {$data["description"]}";
        } elseif (isset($data["exam"])) {
            $exam = $data["exam"];
            $response = "Exam details for {$exam["title"]}:\n";
            if (isset($exam["description"])) {
                $response .= "- Description: {$exam["description"]}\n";
            }
            if (isset($exam["total_points"])) {
                $response .= "- Total points: {$exam["total_points"]}\n";
            }
            $response .= "- Status: {$exam["status"]}\n";
            if (isset($exam["questions_count"])) {
                $response .= "- Questions: {$exam["questions_count"]}\n";
            }
        }

        // Schedule data formatting
        elseif (
            isset($data["weekly_schedule"]) &&
            is_array($data["weekly_schedule"])
        ) {
            $response = "Here is your weekly schedule:\n\n";
            $daysOrder = [
                "Monday",
                "Tuesday",
                "Wednesday",
                "Thursday",
                "Friday",
                "Saturday",
                "Sunday",
            ];

            foreach ($daysOrder as $day) {
                if (isset($data["weekly_schedule"][$day])) {
                    $response .= "**{$day}**\n";
                    foreach ($data["weekly_schedule"][$day] as $item) {
                        $response .= "- {$item["time"]}: {$item["title"]}";
                        if (isset($item["location"])) {
                            $response .= " at {$item["location"]}";
                        }
                        $response .= "\n";
                    }
                    $response .= "\n";
                }
            }
        } elseif (
            isset($data["message"]) &&
            strpos($data["message"], "No schedule") !== false
        ) {
            $response =
                "You don't have any schedule items set up yet. You can add schedule items from the Schedule section.";
        }

        // If response is still empty, return a generic response with the data
        if (empty($response)) {
            $response =
                "Here's the information I found: " .
                json_encode($data, JSON_PRETTY_PRINT);
        }

        return $response;
    }

    /**
     * Get a fake response for local development (kept for backward compatibility)
     */
    private function getFakeResponse(string $message): string
    {
        return "This method is no longer in use. Please use real response providers instead.";
    }

    /**
     * Get available AI models for the frontend.
     */
    public function getAvailableModels()
    {
        try {
            // First try to get models from PrismServer if configured
            $registeredModels = PrismServer::prisms()->pluck('name')->toArray();
            
            if (!empty($registeredModels)) {
                return response()->json($registeredModels);
            }
            
            // Fallback to predefined list if PrismServer doesn't provide models
            $fallbackModels = [
                'GPT-4o', 'GPT-4 Turbo', 'GPT-3.5 Turbo',
                'Gemini 1.5 Pro', 'Gemini 1.5 Flash',
                'Claude 3 Opus', 'Claude 3 Sonnet', 'Claude 3 Haiku',
                'GPT-4o Mini',
            ];
            
            return response()->json($fallbackModels);
        } catch (\Exception $e) {
            Log::error('Error fetching available models: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Could not retrieve available models'], 500);
        }
    }

    /**
     * Get available chat styles for the frontend.
     */
    public function getAvailableStyles()
    {
        $styles = [
            'default' => 'Default',
            'creative' => 'Creative',
            'precise' => 'Precise',
            'balanced' => 'Balanced',
        ];
        
        return response()->json($styles);
    }

    /**
     * List all conversations for the current user.
     */
    public function listConversations()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $currentTeamId = $user->currentTeam?->id;
            
            if (!$currentTeamId) {
                return response()->json([]);
            }
            
            $conversations = Conversation::where('team_id', $currentTeamId)
                ->where('user_id', $user->id)
                ->orderBy('last_activity_at', 'desc')
                ->get()
                ->map(function ($chat) {
                    return [
                        'id' => $chat->id,
                        'title' => $chat->title,
                        'model' => $chat->model,
                        'style' => $chat->style,
                        'last_activity' => $chat->last_activity_at->diffForHumans(),
                    ];
                });
            
            return response()->json($conversations);
        } catch (\Exception $e) {
            Log::error('Error listing conversations: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Could not retrieve conversations'], 500);
        }
    }
    
    /**
     * Get a single conversation with its messages.
     */
    public function getConversation($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            // Format response to match expected structure in AiWidget.vue
            return response()->json([
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'model' => $conversation->model,
                    'style' => $conversation->style,
                    'messages' => $conversation->messages()
                        ->orderBy('created_at', 'asc')
                        ->get()
                        ->map(function ($message) {
                            return [
                                'id' => $message->id,
                                'role' => $message->role,
                                'content' => $message->content,
                                'created_at' => $message->created_at->format('g:i A')
                            ];
                        })
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving conversation: ' . $e->getMessage(), [
                'exception' => $e,
                'conversation_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Could not retrieve conversation'], 500);
        }
    }
    
    /**
     * Update conversation model.
     */
    public function updateModel(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $validatedData = $request->validate([
                'model' => 'required|string'
            ]);
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $conversation->update([
                'model' => $validatedData['model'],
                'last_activity_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating conversation model: ' . $e->getMessage(), [
                'exception' => $e,
                'conversation_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Could not update conversation model'], 500);
        }
    }
    
    /**
     * Update conversation style.
     */
    public function updateStyle(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $validatedData = $request->validate([
                'style' => 'required|string'
            ]);
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $conversation->update([
                'style' => $validatedData['style'],
                'last_activity_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating conversation style: ' . $e->getMessage(), [
                'exception' => $e,
                'conversation_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Could not update conversation style'], 500);
        }
    }
    
    /**
     * Delete a conversation.
     */
    public function deleteConversation($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            // Delete associated messages first
            $conversation->messages()->delete();
            
            // Then delete the conversation
            $conversation->delete();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error deleting conversation: ' . $e->getMessage(), [
                'exception' => $e,
                'conversation_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Could not delete conversation'], 500);
        }
    }

    /**
     * List recent conversations for the current user (limited to 5).
     */
    public function listRecentConversations()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $currentTeamId = $user->currentTeam?->id;
            
            if (!$currentTeamId) {
                return response()->json([]);
            }
            
            $conversations = Conversation::where('team_id', $currentTeamId)
                ->where('user_id', $user->id)
                ->orderBy('last_activity_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($chat) {
                    return [
                        'id' => $chat->id,
                        'title' => $chat->title,
                        'model' => $chat->model,
                        'last_activity' => $chat->last_activity_at->diffForHumans(),
                    ];
                });
            
            return response()->json($conversations);
        } catch (\Exception $e) {
            Log::error('Error listing recent conversations: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Could not retrieve recent conversations'], 500);
        }
    }

    /**
     * Test direct tool execution (for debugging)
     */
    public function testStudentTool(Request $request)
    {
        try {
            $query = $request->input('query', 'Who is Emma in my class?');
            $gradingService = app(GradingService::class);
            $studentTool = new StudentTool($gradingService);
            
            // Log the tool test attempt
            Log::info('Testing StudentTool directly', [
                'query' => $query
            ]);
            
            // Try to search for students
            $result = $studentTool->__invoke('list_students', $query);
            
            // Format the result
            $formattedResult = is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT);
            
            return response()->json([
                'success' => true,
                'query' => $query,
                'raw_result' => $result,
                'formatted_result' => $formattedResult
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing StudentTool: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Direct test of Prism library with tools enabled (for debugging)
     */
    public function testPrismTools(Request $request)
    {
        try {
            $prompt = $request->input('prompt', 'Who is Emma in my class?');
            $modelId = $request->input('model', 'GPT-4o');
            
            // Get user for context
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            
            // Map model to provider
            [$provider, $modelName] = $this->mapModelToProviderAndModel($modelId);
            
            // Get tools
            $tools = $this->getAvailableTools();
            
            // Build a simple system prompt with tool usage info
            $systemPrompt = "You are a Teacher Assistant helping with student information. " . 
                "Use the tools provided to answer questions about students. " .
                "For student queries like 'Who is [name]', always use the student_data tool.";
            
            // Log the test
            Log::info('Testing Prism directly', [
                'prompt' => $prompt,
                'provider' => $provider,
                'model' => $modelName,
                'tools_count' => count($tools)
            ]);
            
            // Build the request to Prism
            $prismRequest = Prism::text()
                ->using($provider, $modelName)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt);
                
            // Add tools if supported
            $providerSupportsTools = in_array(strtolower($provider), ['openai', 'anthropic', 'gemini']);
            if ($providerSupportsTools && !empty($tools)) {
                $prismRequest = $prismRequest
                    ->withTools($tools)
                    ->withMaxSteps(3);
                    
                Log::info('Added tools to Prism test request');
            }
            
            // Generate response
            $response = $prismRequest->generate();
            
            // Extract tool usage info
            $toolCalls = [];
            $toolResults = [];
            
            if (isset($response->toolCalls)) {
                foreach ($response->toolCalls as $call) {
                    $toolCalls[] = [
                        'name' => $call->name,
                        'arguments' => $call->arguments ?? $call->args ?? []
                    ];
                }
            }
            
            if (isset($response->toolResults)) {
                foreach ($response->toolResults as $result) {
                    $toolResults[] = [
                        'name' => $result->toolName,
                        'result' => $result->result
                    ];
                }
            }
            
            // Return the result
            return response()->json([
                'success' => true,
                'prompt' => $prompt,
                'response' => $response->text,
                'tool_calls' => $toolCalls,
                'tool_results' => $toolResults,
                'finish_reason' => $response->finishReason ? $response->finishReason->value : null,
                'provider_used' => $provider,
                'model_used' => $modelName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error testing Prism directly: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
