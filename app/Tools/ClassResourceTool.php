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
        $team = $user?->currentTeam;
        
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

        // --- Extract context ---
        $actual_resource_id = $resource_id;
        $resource_title = null;  // Initialize title variable
        
        // Look for resource title in the message
        if ($query_type === 'get_resource_content') {
            // First extract the resource_uuid part if present
            if ($resource_id && Str::contains($resource_id, 'resource_uuid:')) {
                $actual_resource_id = Str::after($resource_id, 'resource_uuid:');
                Log::info('Extracted UUID from prefix', [
                    'original' => $resource_id,
                    'extracted' => $actual_resource_id
                ]);
            }
            
            // Try to extract title from the full resource_id string
            // Look for a title followed by resource_uuid pattern
            if (preg_match('/@([^\s]+)\s+resource_uuid:/i', $resource_id, $matches)) {
                $resource_title = $matches[1];
                Log::info('Extracted resource title from resource_id', ['title' => $resource_title]);
            }
            // If we didn't find it there, check if it's just the resource_id by itself
            elseif (preg_match('/^@([^\s]+)$/i', $resource_id, $matches)) {
                $resource_title = $matches[1];
                Log::info('Extracted resource title directly', ['title' => $resource_title]);
            }
            
            // Clean UUID for searching
            $actual_resource_id = trim($actual_resource_id);
            
            // Ensure the UUID is properly formatted to avoid SQL errors
            if (!empty($actual_resource_id)) {
                // Remove any characters that aren't valid in a UUID
                $actual_resource_id = preg_replace('/[^a-f0-9\-]/i', '', $actual_resource_id);
                
                // If we have something close to a UUID length, make sure it's properly formatted
                if (strlen($actual_resource_id) >= 32) {
                    // Ensure we have a properly formatted UUID with dashes
                    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $actual_resource_id)) {
                        // Remove any existing dashes
                        $actual_resource_id = str_replace('-', '', $actual_resource_id);
                        // Truncate or pad to exactly 32 chars
                        $actual_resource_id = substr(str_pad($actual_resource_id, 32, '0'), 0, 32);
                        // Insert dashes in the correct positions
                        $actual_resource_id = substr($actual_resource_id, 0, 8) . '-' . 
                                              substr($actual_resource_id, 8, 4) . '-' . 
                                              substr($actual_resource_id, 12, 4) . '-' . 
                                              substr($actual_resource_id, 16, 4) . '-' . 
                                              substr($actual_resource_id, 20, 12);
                        
                        Log::info('Reformatted UUID for compatibility', [
                            'reformatted' => $actual_resource_id
                        ]);
                    }
                }
            }
            
            Log::info('Final lookup parameters', [
                'resource_id' => $actual_resource_id, 
                'resource_title' => $resource_title
            ]);
        }
        
        // Basic parameter validation
        if ($query_type === 'get_resource_content' && empty($actual_resource_id) && empty($resource_title)) {
            return "I need a resource ID or title to retrieve content. Try mentioning a specific resource using @.";
        }

        try {
            // Use resource ID and/or title
            $result = match ($query_type) {
                'find_resources' => $this->findClassResources($team, $user, $search_query, $category_type),
                'get_resource_content' => $this->getResourceContent($team, $user, $actual_resource_id, $resource_title),
                default => ['error' => 'Invalid query_type for ClassResourceTool: '.$query_type],
            };
        } catch (\Exception $e) {
            Log::error('ClassResourceTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return "I encountered an issue when trying to access that resource. The error was: " . $e->getMessage();
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
             $resourceQuery->where(function ($q) use ($searchQuery): void {
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

    private function getResourceContent($team, $user, string $resourceId, ?string $resourceTitle = null): string
    {
        Log::debug('getResourceContent internal call', compact('resourceId', 'resourceTitle'));

        // Clean the resource ID of any non-alphanumeric and hyphen characters
        $cleanResourceId = preg_replace('/[^a-f0-9\-]/i', '', $resourceId);
        
        Log::info('Attempting to find resource with ID', [
            'original' => $resourceId,
            'cleaned' => $cleanResourceId,
            'title' => $resourceTitle
        ]);
        
        try {
            $resource = null;
            
            // Approach 1: Try exact UUID match if it looks like a valid UUID
            if (!empty($cleanResourceId) && strlen($cleanResourceId) >= 32) {
                // Format UUID with dashes if they're missing
                if (!Str::contains($cleanResourceId, '-')) {
                    $formattedUuid = substr($cleanResourceId, 0, 8) . '-' . 
                                     substr($cleanResourceId, 8, 4) . '-' . 
                                     substr($cleanResourceId, 12, 4) . '-' . 
                                     substr($cleanResourceId, 16, 4) . '-' . 
                                     substr($cleanResourceId, 20);
                    Log::info('Formatted UUID for lookup', ['formatted' => $formattedUuid]);
                } else {
                    $formattedUuid = $cleanResourceId;
                }
                
                try {
                    // Use a try/catch to handle potential PostgreSQL UUID format errors
                    // Use query bindings to let Laravel handle the type correctly
                    $resource = ClassResource::query()
                        ->where('team_id', $team->id)
                        ->whereRaw('id::text = ?', [$formattedUuid])
                        ->first();
                    
                    if ($resource) {
                        Log::info('Found resource by exact UUID match', [
                            'id' => $resource->id,
                            'title' => $resource->title
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('UUID lookup failed', ['error' => $e->getMessage()]);
                    // Continue to other approaches
                }
            }
            
            // Approach 2: Exact title match
            if (!$resource && !empty($resourceTitle)) {
                $resource = ClassResource::where('title', $resourceTitle)
                    ->where('team_id', $team->id)
                    ->first();
                
                if ($resource) {
                    Log::info('Found resource by exact title match', [
                        'id' => $resource->id,
                        'title' => $resource->title
                    ]);
                }
            }
            
            // Approach 3: Case-insensitive title match
            if (!$resource && !empty($resourceTitle)) {
                $resource = ClassResource::whereRaw('LOWER(title) = ?', [strtolower($resourceTitle)])
                    ->where('team_id', $team->id)
                    ->first();
                
                if ($resource) {
                    Log::info('Found resource by case-insensitive title match', [
                        'id' => $resource->id,
                        'title' => $resource->title
                    ]);
                }
            }
            
            // Approach 4: Partial UUID match (first 8 characters)
            if (!$resource && !empty($cleanResourceId) && strlen($cleanResourceId) >= 8) {
                $idPrefix = substr($cleanResourceId, 0, 8);
                
                Log::info('Trying partial UUID match with prefix', ['prefix' => $idPrefix]);
                
                try {
                    // Use text casting for PostgreSQL UUID type compatibility with proper binding
                    $resources = ClassResource::query()
                        ->where('team_id', $team->id)
                        ->whereRaw('CAST(id AS TEXT) LIKE ?', [$idPrefix.'%'])
                        ->orderBy('updated_at', 'desc')
                        ->limit(5)
                        ->get();
                    
                    if ($resources->isNotEmpty()) {
                        $resource = $resources->first();
                        Log::info('Found resource with partial UUID match', [
                            'search_prefix' => $idPrefix,
                            'matched_id' => $resource->id,
                            'candidates_count' => $resources->count()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Partial UUID lookup failed', ['error' => $e->getMessage()]);
                    // Continue to other approaches
                }
            }
            
            // Approach 5: Title partial match (more flexible search)
            if (!$resource && !empty($resourceTitle)) {
                // Try to find resources with similar titles
                $resources = ClassResource::where('title', 'LIKE', "%$resourceTitle%")
                    ->where('team_id', $team->id)
                    ->orderBy('is_pinned', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->limit(1)
                    ->get();
                
                if ($resources->isNotEmpty()) {
                    $resource = $resources->first();
                    Log::info('Found resource by partial title match', [
                        'id' => $resource->id, 
                        'title' => $resource->title,
                        'search_title' => $resourceTitle
                    ]);
                }
            }
            
            // Approach 6: Fuzzy search - first few letters of each word
            if (!$resource && !empty($resourceTitle) && strlen($resourceTitle) >= 3) {
                $words = explode(' ', $resourceTitle);
                $firstFewLetters = [];
                
                foreach ($words as $word) {
                    if (strlen($word) >= 3) {
                        $firstFewLetters[] = substr($word, 0, 3);
                    }
                }
                
                if (!empty($firstFewLetters)) {
                    $query = ClassResource::where('team_id', $team->id);
                    
                    foreach ($firstFewLetters as $letters) {
                        $query->where('title', 'LIKE', "%$letters%");
                    }
                    
                    $resource = $query->orderBy('updated_at', 'desc')->first();
                    
                    if ($resource) {
                        Log::info('Found resource by fuzzy search', [
                            'id' => $resource->id,
                            'title' => $resource->title,
                            'search_patterns' => $firstFewLetters
                        ]);
                    }
                }
            }
            
            // Fallback: Most recent resource as last resort
            if (!$resource) {
                Log::warning('All lookup approaches failed, using most recent as fallback');
                $resource = ClassResource::where('team_id', $team->id)
                    ->where(fn ($q) => $q->where('is_archived', false)->orWhereNull('is_archived'))
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($resource) {
                    Log::info('Using fallback resource (most recent)', [
                        'resource_id' => $resource->id,
                        'title' => $resource->title
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error finding resource', [
                'error' => $e->getMessage(),
                'id' => $cleanResourceId,
                'title' => $resourceTitle
            ]);
            return "I couldn't find the resource you mentioned. The error was: " . $e->getMessage();
        }

        if (! $resource) {
            return "I couldn't find any resources in this team. Would you like me to help you create some?";
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

        $query->where(function ($q) use ($user, $team): void {
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