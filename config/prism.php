<?php
use App\Enums\ProviderFeature;
$featureMap = [
    "openai" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        ProviderFeature::Structured,
        ProviderFeature::Embeddings,
        ProviderFeature::Image,
        ProviderFeature::Tools,
        // ProviderFeature::Documents, // Not explicitly marked in OpenAI row
    ],
    "anthropic" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        ProviderFeature::Structured,
        // ProviderFeature::Embeddings, // Not explicitly marked
        ProviderFeature::Image,
        ProviderFeature::Tools,
        // ProviderFeature::Documents, // Not explicitly marked
    ],
    "ollama" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        ProviderFeature::Structured,
        ProviderFeature::Embeddings,
        ProviderFeature::Image,
        ProviderFeature::Tools,
        ProviderFeature::Documents, // Marked in Ollama row
    ],
    "mistral" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        ProviderFeature::Structured,
        ProviderFeature::Embeddings,
        // ProviderFeature::Image, // Not explicitly marked
        ProviderFeature::Tools,
        // ProviderFeature::Documents, // Not explicitly marked
    ],
    "groq" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        ProviderFeature::Structured,
        // ProviderFeature::Embeddings, // Not explicitly marked
        // ProviderFeature::Image, // Not explicitly marked
        ProviderFeature::Tools,
        // ProviderFeature::Documents, // Not explicitly marked
    ],
    "xai" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        // ProviderFeature::Structured, // Not explicitly marked
        // ProviderFeature::Embeddings, // Not explicitly marked
        // ProviderFeature::Image, // Not explicitly marked
        // ProviderFeature::Tools, // Not explicitly marked
        // ProviderFeature::Documents, // Not explicitly marked
    ],
    "gemini" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        ProviderFeature::Structured,
        ProviderFeature::Embeddings,
        ProviderFeature::Image,
        ProviderFeature::Tools,
        // ProviderFeature::Documents, // Not explicitly marked in Gemini row
    ],
    "deepseek" => [
        ProviderFeature::Text,
        ProviderFeature::Streaming,
        // ProviderFeature::Structured, // Not explicitly marked
        ProviderFeature::Embeddings,
        // ProviderFeature::Image, // Not explicitly marked
        ProviderFeature::Tools,
        // ProviderFeature::Documents, // Not explicitly marked
    ],
    "voyageai" => [
        // ProviderFeature::Text, // Not explicitly marked
        // ProviderFeature::Streaming, // Not explicitly marked
        // ProviderFeature::Structured, // Not explicitly marked
        ProviderFeature::Embeddings,
        // ProviderFeature::Image, // Not explicitly marked
        // ProviderFeature::Tools, // Not explicitly marked
        // ProviderFeature::Documents, // Not explicitly marked
    ],
    // Add mappings for other providers like Azure OpenAI, Bedrock if needed and present in the table/config
];

// Build the providers config array dynamically adding features
$providersConfig = [
    "openai" => [
        "url" => env("OPENAI_URL", "https://api.openai.com/v1"),
        "api_key" => env("OPENAI_API_KEY", ""),
        "organization" => env("OPENAI_ORGANIZATION", null),
        "project" => env("OPENAI_PROJECT", null),
    ],
    "anthropic" => [
        "api_key" => env("ANTHROPIC_API_KEY", ""),
        "version" => env("ANTHROPIC_API_VERSION", "2023-06-01"),
        "default_thinking_budget" => env(
            "ANTHROPIC_DEFAULT_THINKING_BUDGET",
            1024
        ),
        // Include beta strings as a comma separated list.
        "anthropic_beta" => env("ANTHROPIC_BETA", null),
    ],
    "ollama" => [
        "url" => env("OLLAMA_URL", "http://localhost:11434"),
    ],
    "mistral" => [
        "api_key" => env("MISTRAL_API_KEY", ""),
        "url" => env("MISTRAL_URL", "https://api.mistral.ai/v1"),
    ],
    "groq" => [
        "api_key" => env("GROQ_API_KEY", ""),
        "url" => env("GROQ_URL", "https://api.groq.com/openai/v1"),
    ],
    "xai" => [
        "api_key" => env("XAI_API_KEY", ""),
        "url" => env("XAI_URL", "https://api.x.ai/v1"),
    ],
    "gemini" => [
        "api_key" => env("GEMINI_API_KEY", ""),
        "url" => env(
            "GEMINI_URL",
            "https://generativelanguage.googleapis.com/v1beta/models"
        ),
    ],
    "deepseek" => [
        "api_key" => env("DEEPSEEK_API_KEY", ""),
        // Add URL if Deepseek requires one via config
        // 'url' => env('DEEPSEEK_URL', '...'),
    ],
    "voyageai" => [
        "api_key" => env("VOYAGEAI_API_KEY", ""),
        "url" => env("VOYAGEAI_URL", "https://api.voyageai.com/v1"),
    ],
];

// Add the 'features' array to each provider config
foreach ($providersConfig as $providerKey => &$config) {
    // Use null coalescing operator to default to an empty array if the provider isn't in the map
    $config["features"] = $featureMap[$providerKey] ?? [];
}
unset($config); // Unset reference to last element

return [
    "prism_server" => [
        // The middleware that will be applied to the Prism Server routes.
        "middleware" => [],
        "enabled" => env("PRISM_SERVER_ENABLED", true),
    ],
    "providers" => $providersConfig, // Use the dynamically built array
];
