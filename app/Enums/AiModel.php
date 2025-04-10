<?php

namespace App\Enums;

use Prism\Prism\Enums\Provider;
use Illuminate\Support\Facades\Log;

/**
 * Represents the available AI models selectable by the user.
 *
 * Maps user-friendly names to the specific provider and model identifier
 * required by the AI service (like Prism).
 */
enum AiModel: string
{
    // Define cases based on the user-facing names in your controller/frontend
    case GPT_4o = "GPT-4o";
    case GPT_4o_Mini = "GPT-4o Mini";
    case GPT_4_Turbo = "GPT-4 Turbo";
    case GPT_3_5_Turbo = "GPT-3.5 Turbo";
    case Gemini_1_5_Pro = "Gemini 1.5 Pro";
    case Gemini_1_5_Flash = "Gemini 1.5 Flash";
    case Gemini_Pro = "Gemini Pro"; // Assuming this might be needed based on mapModelToProviderAndModel comments
    case Claude_3_Opus = "Claude 3 Opus";
    case Claude_3_Sonnet = "Claude 3 Sonnet";
    case Claude_3_Haiku = "Claude 3 Haiku";
    // Note: Gemini 2.0 Flash mentioned in mapModelToProviderAndModel might need verification if it's a distinct ID
    // case Gemini_2_0_Flash = 'Gemini 2.0 Flash';

    /**
     * Get the user-friendly label for the model.
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Map the Enum case to the corresponding Prism provider and model name.
     * Mirrors the logic in AiStreamController::mapModelToProviderAndModel fallback map.
     *
     * @return array{0: string, 1: string} [Provider value, Model Name]
     */
    public function mapToPrism(): array
    {
        // This map should ideally be kept in sync with the controller's map
        // or ideally, read from a central configuration/service.
        $providerMap = [
            self::GPT_4o->value => [Provider::OpenAI->value, "gpt-4o"],
            self::GPT_4o_Mini->value => [
                Provider::OpenAI->value,
                "gpt-4o-mini",
            ],
            self::GPT_4_Turbo->value => [
                Provider::OpenAI->value,
                "gpt-4-turbo",
            ],
            self::GPT_3_5_Turbo->value => [
                Provider::OpenAI->value,
                "gpt-3.5-turbo",
            ],
            self::Gemini_1_5_Pro->value => [
                Provider::Gemini->value,
                "gemini-1.5-pro-latest",
            ],
            self::Gemini_1_5_Flash->value => [
                Provider::Gemini->value,
                "gemini-1.5-flash-latest",
            ],
            self::Gemini_Pro->value => [Provider::Gemini->value, "gemini-pro"],
            self::Claude_3_Opus->value => [
                Provider::Anthropic->value,
                "claude-3-opus-20240229",
            ],
            self::Claude_3_Sonnet->value => [
                Provider::Anthropic->value,
                "claude-3-sonnet-20240229",
            ],
            self::Claude_3_Haiku->value => [
                Provider::Anthropic->value,
                "claude-3-haiku-20240307",
            ],
            // self::Gemini_2_0_Flash->value => [Provider::Gemini->value, "gemini-2.0-flash"], // If needed
        ];

        if (isset($providerMap[$this->value])) {
            return $providerMap[$this->value];
        }

        // Fallback or default if a case is added without updating the map
        Log::warning(
            "AI Model Enum case '{$this->value}' not found in mapToPrism. Defaulting.",
            ["enum_case" => $this->value]
        );
        return [Provider::OpenAI->value, "gpt-4o"]; // Default from controller
    }

    /**
     * Get all available model options as an array for frontend dropdowns etc.
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
