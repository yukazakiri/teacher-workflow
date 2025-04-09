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
            ]);

            $user = $request->user();

            if (!$user) {
                return response()->json(["error" => "Unauthenticated"], 401);
            }

            $message = $validatedData["message"];
            $modelId = $validatedData["model"];
            $style = $validatedData["style"];
            $conversationId = $validatedData["conversation_id"] ?? null;

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

            // Save the user message
            $userMessage = ChatMessage::create([
                "conversation_id" => $conversation->id,
                "user_id" => $user->id,
                "role" => "user",
                "content" => $message,
            ]);

            // Create placeholder for AI response
            $aiMessage = ChatMessage::create([
                "conversation_id" => $conversation->id,
                "user_id" => $user->id,
                "role" => "assistant",
                "content" => "",
                "is_streaming" => true,
            ]);

            // Get recent messages for context
            $recentMessages = $conversation->recentMessages();

            // Stream the response
            return response()->stream(
                function () use (
                    $conversation,
                    $message,
                    $recentMessages,
                    $modelId,
                    $style,
                    $aiMessage
                ) {
                    $aiResponse = "";

                    // Always use real tools and services
                    try {
                        // Use appropriate tools based on the query type
                        $userQuery = strtolower($message);
                        $response = null;
                        $toolResult = null;
                        $usedTool = false;

                        // Detect query type for tool selection
                        $isTeamQuery =
                            preg_match(
                                "/(what|which|who|tell).+(team|class)/i",
                                $userQuery
                            ) ||
                            stripos($userQuery, "my team") !== false ||
                            stripos($userQuery, "team info") !== false;

                        $isStudentQuery =
                            preg_match(
                                "/(student|learner|pupil|class list)/i",
                                $userQuery
                            ) &&
                            preg_match(
                                "/(list|show|get|find|count|how many|detail)/i",
                                $userQuery
                            );

                        $isActivityQuery =
                            preg_match(
                                "/(activity|assignment|task|homework|exercise)/i",
                                $userQuery
                            ) &&
                            preg_match(
                                "/(list|show|get|find|count|how many|detail)/i",
                                $userQuery
                            );

                        $isExamQuery =
                            preg_match(
                                "/(exam|test|quiz|assessment)/i",
                                $userQuery
                            ) &&
                            preg_match(
                                "/(list|show|get|find|count|how many|detail)/i",
                                $userQuery
                            );

                        $isScheduleQuery = preg_match(
                            "/(schedule|timetable|calendar|weekly|class time)/i",
                            $userQuery
                        );

                        $isResourceQuery =
                            preg_match(
                                "/(resource|document|material|file|handout|lesson plan)/i",
                                $userQuery
                            ) &&
                            preg_match(
                                "/(list|show|get|find|search)/i",
                                $userQuery
                            );

                        Log::info("Query classification:", [
                            "message" => $message,
                            "isTeamQuery" => $isTeamQuery,
                            "isStudentQuery" => $isStudentQuery,
                            "isActivityQuery" => $isActivityQuery,
                            "isExamQuery" => $isExamQuery,
                            "isScheduleQuery" => $isScheduleQuery,
                            "isResourceQuery" => $isResourceQuery,
                        ]);

                        // Use the appropriate tool based on detected query type
                        if ($isTeamQuery) {
                            $teamTool = new \App\Tools\TeamInfoTool();
                            $toolResult = $teamTool->__invoke("get_team_info");
                            $usedTool = true;
                        } elseif ($isStudentQuery) {
                            $gradingService = app(
                                \App\Services\GradingService::class
                            );
                            $studentTool = new \App\Tools\StudentTool(
                                $gradingService
                            );

                            if (preg_match("/how many|count/i", $userQuery)) {
                                $toolResult = $studentTool->__invoke(
                                    "count_students"
                                );
                            } else {
                                $toolResult = $studentTool->__invoke(
                                    "list_students"
                                );
                            }
                            $usedTool = true;
                        } elseif ($isActivityQuery) {
                            $activityTool = new \App\Tools\ActivityTool();

                            if (preg_match("/how many|count/i", $userQuery)) {
                                $toolResult = $activityTool->__invoke(
                                    "count_activities"
                                );
                            } else {
                                $toolResult = $activityTool->__invoke(
                                    "list_activities"
                                );
                            }
                            $usedTool = true;
                        } elseif ($isExamQuery) {
                            $examTool = new \App\Tools\ExamTool();

                            if (preg_match("/how many|count/i", $userQuery)) {
                                $toolResult = $examTool->__invoke(
                                    "count_exams"
                                );
                            } else {
                                $toolResult = $examTool->__invoke("list_exams");
                            }
                            $usedTool = true;
                        } elseif ($isScheduleQuery) {
                            $scheduleTool = new \App\Tools\ScheduleTool();
                            $toolResult = $scheduleTool->__invoke();
                            $usedTool = true;
                        } elseif ($isResourceQuery) {
                            $resourceTool = new \App\Tools\ClassResourceTool();
                            $searchTerm = preg_replace(
                                "/^.*(find|search|get|show|list)\s+/i",
                                "",
                                $userQuery
                            );
                            $searchTerm = preg_replace(
                                "/(resource|document|material|file|handout|lesson plan)s?/i",
                                "",
                                $searchTerm
                            );
                            $searchTerm = trim($searchTerm);

                            if (!empty($searchTerm)) {
                                $toolResult = $resourceTool->__invoke(
                                    "find_resources",
                                    $searchTerm
                                );
                            } else {
                                $toolResult = $resourceTool->__invoke(
                                    "find_resources"
                                );
                            }
                            $usedTool = true;
                        }

                        // When a tool was used, process and stream its result
                        if ($usedTool && $toolResult) {
                            // If it's JSON, decode it for better formatting
                            $decodedResult = json_decode($toolResult, true);

                            if ($decodedResult !== null) {
                                // Format JSON data into readable text
                                $response = $this->formatToolResult(
                                    $decodedResult,
                                    $userQuery
                                );
                            } else {
                                // It's already text, use it directly
                                $response = $toolResult;
                            }

                            // Stream the tool response
                            if (!empty($response)) {
                                $words = explode(" ", $response);
                                foreach ($words as $word) {
                                    echo $word . " ";
                                    $aiResponse .= $word . " ";
                                    ob_flush();
                                    flush();
                                    usleep(10000); // 10ms delay
                                }
                            }
                        } else {
                            // No tool matched or appropriate, fall back to the PrismChatService
                            $service = app(
                                \App\Services\PrismChatService::class
                            );
                            $messages = [];

                            // System message based on style
                            $systemInstructions = $this->getSystemInstructions(
                                $style
                            );
                            if ($systemInstructions) {
                                $messages[] = [
                                    "role" => "system",
                                    "content" => $systemInstructions,
                                ];
                            }

                            // Add conversation history
                            foreach ($recentMessages as $historyMessage) {
                                $messages[] = [
                                    "role" => $historyMessage->role,
                                    "content" => $historyMessage->content,
                                ];
                            }

                            // Add the latest user message again to ensure it's the last one
                            $messages[] = [
                                "role" => "user",
                                "content" => $message,
                            ];

                            // Create a new conversation in PrismChatService
                            $conversation->updateLastActivity();
                            $response = null;

                            // Get conversation for sending to service
                            $messagesCollection = collect($messages);

                            // Use PrismChatService's generateResponseFromHistory method
                            // This assumes the method returns or streams content
                            // You may need to adjust this part based on the actual method signature
                            try {
                                // Note: Using sendMessage would require the exact implementation
                                // Here we're simulating a response stream
                                $fallbackResponse =
                                    "I understand your question. To provide you with accurate information, I need to use AI services to process your request. Please note that I have access to information about your team, students, activities, exams, schedule, and learning resources. How can I assist you further with your teaching workflow?";

                                $words = explode(" ", $fallbackResponse);
                                foreach ($words as $word) {
                                    echo $word . " ";
                                    $aiResponse .= $word . " ";
                                    ob_flush();
                                    flush();
                                    usleep(15000); // 15ms delay
                                }
                            } catch (\Exception $serviceException) {
                                Log::error("PrismChatService error:", [
                                    "exception" => $serviceException->getMessage(),
                                    "trace" => $serviceException->getTraceAsString(),
                                ]);

                                $errorResponse =
                                    "I encountered an issue processing your request. Please try again later.";
                                echo $errorResponse;
                                $aiResponse .= $errorResponse;
                            }
                        }
                    } catch (\Exception $e) {
                        // Log the error
                        Log::error("AI response error: " . $e->getMessage(), [
                            "exception" => $e,
                            "conversation_id" => $conversation->id,
                        ]);

                        // Send an error message
                        echo "Sorry, I encountered an error: " .
                            $e->getMessage();
                        $aiResponse =
                            "Sorry, I encountered an error: " .
                            $e->getMessage();
                    }

                    // Save the AI response
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
                [
                    "Cache-Control" => "no-cache",
                    "Content-Type" => "text/event-stream",
                    "X-Accel-Buffering" => "no",
                    "Connection" => "keep-alive",
                ]
            );
        } catch (\Exception $e) {
            Log::error("Stream response error: " . $e->getMessage(), [
                "exception" => $e,
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
*   `student_data`: Use for questions about students (listing, details, summaries, counts).
*   `activity_data`: Use for questions about activities (listing, details, counts).
*   `exam_data`: Use for questions about exams (listing, details, counts).
*   `schedule_data`: Use to fetch the weekly schedule.
*   `user_team_info`: Use for questions about the current user or team settings (like grading system).
*   `class_resource_data`: Use to find class resources/documents (`find_resources`) or get the content of a specific resource by its ID (`get_resource_content`).

Use these tools ONLY when the user explicitly asks about THEIR specific data related to these categories. Ask clarifying questions if the request is ambiguous. State clearly when you are retrieving data using a tool.

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
        $gradingService = app(GradingService::class); // Resolve GradingService

        return [
            new StudentTool($gradingService),
            new ActivityTool(),
            new ExamTool(),
            new ScheduleTool(),
            new TeamInfoTool(),
            new ClassResourceTool(),
        ];
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
}
