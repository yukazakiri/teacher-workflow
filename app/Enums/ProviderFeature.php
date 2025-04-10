<?php
namespace App\Enums; // Or your preferred namespace

/**
 * Represents the features or capabilities that an AI provider/model might support
 * within the context of the Prism library.
 */
enum ProviderFeature: string
{
    /** Basic text input and generation. */
    case Text = "text";

    /** Streaming responses chunk by chunk. */
    case Streaming = "streaming";

    /** Requesting structured output (e.g., JSON schema). */
    case Structured = "structured";

    /** Generating text embeddings (vector representations). */
    case Embeddings = "embeddings";

    /** Processing image inputs (multimodal). */
    case Image = "image";

    /** Using tools/function calling. */
    case Tools = "tools";

    /** Specific handling or processing of documents (e.g., for RAG). */
    case Documents = "documents";

    /**
     * Get a user-friendly label for the feature.
     */
    public function label(): string
    {
        return match ($this) {
            self::Text => "Text Generation",
            self::Streaming => "Streaming Responses",
            self::Structured => "Structured Output",
            self::Embeddings => "Embeddings",
            self::Image => "Image Input",
            self::Tools => "Tool Use / Function Calling",
            self::Documents => "Document Processing",
        };
    }

    /**
     * Get a brief description of the feature.
     */
    public function description(): string
    {
        return match ($this) {
            self::Text => "Supports basic text input and generation.",
            self::Streaming
                => "Capable of sending response data in chunks as it becomes available.",
            self::Structured
                => "Can generate output conforming to a specified structure, like a JSON schema.",
            self::Embeddings
                => "Can create numerical vector representations (embeddings) of text.",
            self::Image
                => "Supports accepting images as part of the input (multimodal capability).",
            self::Tools
                => "Allows the model to invoke predefined functions or tools to interact with external systems or data.",
            self::Documents
                => "Offers specialized features for handling or processing document inputs, potentially for Retrieval-Augmented Generation (RAG) or analysis.",
        };
    }

    /**
     * Get all feature options as an array suitable for dropdowns etc.
     *
     * @return array<string, string> ['value' => 'Label']
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
