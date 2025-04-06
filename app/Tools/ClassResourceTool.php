<?php

declare(strict_types=1);

namespace App\Tools;

use App\Models\ClassResource;
use App\Models\ResourceCategory;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool as BaseTool;
use Smalot\PdfParser\Parser as PdfParser;

class ClassResourceTool extends BaseTool
{
    public function __construct()
    {
        $this->as('class_resource_data')
            ->for(
                "Finds shared class resources/documents/materials or retrieves the content of a specific resource. " .
                "Use ONLY for queries like 'find lesson plan about X', 'get document Y', 'search teaching materials for Z', 'what does [Resource: Title ID: UUID] say?'."
            )
            ->withParameter(
                new EnumSchema(
                    name: 'query_type',
                    description: 'The type of resource operation.',
                    options: [
                        'find_resources',     // To search/list resources
                        'get_resource_content' // To fetch content of a specific resource
                    ]
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'search_query',
                    description: "Optional search term for resource title or description. Used only with query_type 'find_resources'."
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'category_type',
                    description: "Optional category type filter (teaching, student, admin, uncategorized). Used only with query_type 'find_resources'."
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'resource_id',
                    description: "The unique UUID of a class resource. REQUIRED for query_type 'get_resource_content'. The user message may contain 'resource_uuid:UUID', but provide ONLY the UUID part here. The tool handles the prefix internally."
                )
            )
            ->using($this);
    }

    public function __invoke(
        string $query_type,
        ?string $search_query = null,
        ?string $category_type = null,
        ?string $resource_id = null
    ): string {
        $user = Auth::user();
        // Note: Using Filament facade for tenant might be specific to Filament context.
        // Ensure this works correctly in the context where Prism/tools are invoked.
        // If running outside Filament HTTP requests, consider passing Team explicitly or resolving differently.
        $team = Filament::getTenant(); // Or $user?->currentTeam; ensure consistency

        if (! $user || ! $team) {
            return $this->encodeError('User or team context not found.');
        }

        Log::info('ClassResourceTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'query_type' => $query_type,
            'search_query' => $search_query,
            'category_type' => $category_type,
            'resource_id' => $resource_id,
        ]);

        // --- Start: Extract UUID if using resource_uuid: prefix ---
        $actual_resource_id = $resource_id;
        if ($query_type === 'get_resource_content' && $resource_id && Str::startsWith($resource_id, 'resource_uuid:')) {
            $actual_resource_id = Str::after($resource_id, 'resource_uuid:');
            Log::info('Extracted UUID from prefixed ID', ['original' => $resource_id, 'extracted' => $actual_resource_id]);
        }
        // --- End: Extract UUID ---

        // Parameter validation using the potentially extracted ID
        if ($query_type === 'get_resource_content' && ! $actual_resource_id) {
            return $this->encodeError('The resource_id parameter is required (or could not be extracted) for query_type: get_resource_content.');
        }
         if ($query_type === 'find_resources' && $actual_resource_id) {
             // Avoid confusion if both search and specific ID are provided for wrong query type
             Log::warning('Resource ID provided for find_resources query type, ignoring it.', ['resource_id' => $actual_resource_id]);
             // $actual_resource_id = null; // Don't nullify here, just ignore in findClassResources logic
         }
         if ($query_type === 'get_resource_content' && ($search_query || $category_type)) {
             Log::warning('Search query or category type provided for get_resource_content query type, ignoring them.');
             $search_query = null;
             $category_type = null;
         }

        try {
            // Use $actual_resource_id for getResourceContent
            $result = match ($query_type) {
                'find_resources' => $this->findClassResources($team, $user, $search_query, $category_type), // findClassResources doesn't use resource_id
                'get_resource_content' => $this->getResourceContent($team, $user, $actual_resource_id),
                default => ['error' => 'Invalid query_type for ClassResourceTool: '.$query_type],
            };
        } catch (\Exception $e) {
            Log::error('ClassResourceTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Return error as string for direct output
            return $this->encodeError('An internal error occurred: '.$e->getMessage());
        }

        // findClassResources and getResourceContent already return strings
        if (is_string($result)) {
             // Optional: Limit string length here too, if not done internally
             return $this->limitStringResult($result);
        } else {
             // Should not happen with current logic, but good practice
             Log::error('ClassResourceTool unexpected result type', ['result' => $result]);
             return $this->encodeError('Unexpected result format from resource tool operation.');
        }
    }

    private function findClassResources($team, $user, ?string $query = null, ?string $categoryType = null, int $limit = 5): string
    {
        Log::debug('findClassResources internal call', compact('query', 'categoryType', 'limit'));

        $resourceQuery = ClassResource::query()
            ->where('team_id', $team->id)
            ->where(fn ($q) => $q->where('is_archived', false)->orWhereNull('is_archived'))
            ->with(['category', 'media']);

        if (! empty($categoryType)) {
            $categoryType = strtolower(trim($categoryType));
            $validTypes = ['teaching', 'student', 'admin'];
            if (in_array($categoryType, $validTypes)) {
                 $categoryIds = ResourceCategory::where('team_id', $team->id)->where('type', $categoryType)->pluck('id');
                 if ($categoryIds->isNotEmpty()) $resourceQuery->whereIn('category_id', $categoryIds);
                 else return "No categories found for type '{$categoryType}', so no matching resources."; // Early exit
            } elseif ($categoryType === 'uncategorized') {
                 $resourceQuery->whereNull('category_id');
            } else {
                Log::warning('Invalid categoryType provided', ['categoryType' => $categoryType]);
                // Optionally inform AI/user, maybe return error string? For now, we ignore invalid type.
            }
        }

        if (! empty($query)) {
             $searchQuery = trim($query);
             $resourceQuery->where(function ($q) use ($searchQuery) {
                 $q->where('title', 'like', "%{$searchQuery}%")
                   ->orWhere('description', 'like', "%{$searchQuery}%");
             });
        }

        // Apply Permission Filtering
        $this->applyResourcePermissions($resourceQuery, $team, $user);

        $resources = $resourceQuery->orderBy('is_pinned', 'desc')
                                    ->orderBy('updated_at', 'desc')
                                    ->limit($limit)
                                    ->get();

        if ($resources->isEmpty()) {
             $message = "No resources found";
             if ($query) $message .= " matching '{$query}'";
             if ($categoryType) $message .= " in category type '{$categoryType}'";
             $message .= ".";
             return $message;
        }

        $output = "Found {$resources->count()} resource(s):\n";
        foreach ($resources as $index => $resource) {
            $media = $resource->getFirstMedia('resources');
            $fileInfo = $media ? ' ('. strtoupper($media->extension) . ', ID: ' . $resource->id . ')' : ' (ID: ' . $resource->id . ')'; // Include ID
            $categoryName = $resource->category ? $resource->category->name : 'Uncategorized';
            $pinnedMarker = $resource->is_pinned ? '[PINNED] ' : '';
            $output .= ($index + 1) . ". {$pinnedMarker}**{$resource->title}**{$fileInfo} - Category: {$categoryName}\n";
             if (!empty($resource->description)) {
                 $output .= "   > " . Str::limit($resource->description, 100) . "\n";
             }
        }

        Log::info('ClassResourceTool: findClassResources returning results', ['count' => $resources->count()]);
        return trim($output);
    }

    private function getResourceContent($team, $user, string $resourceId): string
    {
        Log::debug('getResourceContent internal call', compact('resourceId'));

        // Validate UUID format
        if (! Str::isUuid($resourceId)) {
             Log::warning('Invalid resource ID format provided.', compact('resourceId'));
             return "Error: Invalid resource ID format provided.";
        }

        $resource = ClassResource::where('id', $resourceId)
                                ->where('team_id', $team->id)
                                ->first();

        if (! $resource) {
            Log::warning('Resource not found or doesn\'t belong to team.', compact('resourceId', 'team'));
            return "Error: Resource with ID '{$resourceId}' not found in this team.";
        }

        // Check Permissions
        if (! $this->userCanAccessResource($resource, $team, $user)) {
            Log::warning('Permission denied for resource.', ['user_id' => $user->id, 'resourceId' => $resourceId, 'access_level' => $resource->access_level]);
            return "Error: You do not have permission to access this resource.";
        }

        if ($resource->is_archived) {
            Log::info('Attempted to access archived resource.', compact('resourceId'));
             return "Error: This resource ('{$resource->title}') is archived.";
        }

        $media = $resource->getFirstMedia('resources');
        if (! $media) {
             Log::error('No media file associated with resource.', compact('resourceId'));
            return "Error: No file is associated with the resource '{$resource->title}'.";
        }

        $filePath = $media->getPath();
        if (! file_exists($filePath)) {
            Log::error('Media file not found at path.', compact('resourceId', 'filePath'));
             return "Error: The file for resource '{$resource->title}' could not be found.";
        }

        $mimeType = $media->mime_type;
        $content = '';

        // Extract content based on MIME type
        if ($mimeType === 'application/pdf') {
            try {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                $content = $pdf->getText();
                if (empty(trim($content))) {
                    Log::warning('PDF parsed but content is empty.', compact('resourceId'));
                    return "Info: The PDF resource '{$resource->title}' was parsed, but no text content could be extracted.";
                }
                Log::info('Extracted PDF content.', ['resourceId' => $resourceId, 'length' => strlen($content)]);
             } catch (\Exception $e) {
                 Log::error('PDF parsing failed.', ['resourceId' => $resourceId, 'error' => $e->getMessage()]);
                 return "Error: Failed to parse the PDF content for resource '{$resource->title}'.";
             }
        } elseif (Str::startsWith($mimeType, 'text/')) {
             $content = file_get_contents($filePath);
             Log::info('Extracted text content.', ['resourceId' => $resourceId, 'length' => strlen($content)]);
        } else {
            Log::warning('Unsupported MIME type for content extraction.', compact('resourceId', 'mimeType'));
             return "Info: Cannot extract text content from this file type ({$mimeType}) for resource '{$resource->title}'.";
        }

        // Add context for the AI
        return "Content of resource '{$resource->title}' (ID: {$resourceId}):\n---\n{$content}\n---";
    }

    private function applyResourcePermissions($query, $team, $user): void
    {
        if ($team->userIsOwner($user)) {
            return; // Owner sees all (within team context)
        }

        $query->where(function ($q) use ($user, $team) {
            $q->where('access_level', 'all')
              ->orWhere('created_by', $user->id); // Creator access

            if ($user->hasTeamRole($team, 'teacher')) {
                $q->orWhere('access_level', 'teacher');
            }
            // Add other roles like 'student' if they have specific access levels
        });
    }

    private function userCanAccessResource($resource, $team, $user): bool
    {
         if ($team->userIsOwner($user)) return true;
         if ($resource->created_by === $user->id) return true;
         if ($resource->access_level === 'all') return true;
         if ($resource->access_level === 'teacher' && $user->hasTeamRole($team, 'teacher')) return true;
         // Add other role checks here if needed

         return false;
    }

    private function encodeError(string $message): string
    {
        // Return simple error string for resource tool
        Log::error('ClassResourceTool Error', ['message' => $message]);
        return "Error: {$message}";
    }

    private function limitStringResult(string $result, int $maxLength = 10000): string
    {
        if (mb_strlen($result) <= $maxLength) {
            return $result;
        }

        Log::info('ClassResourceTool: Truncating long content result.', ['original_length' => strlen($result), 'limit' => $maxLength]);
        // Simple truncation for string results
        return Str::limit($result, $maxLength, '... [Content Truncated]');
    }
} 