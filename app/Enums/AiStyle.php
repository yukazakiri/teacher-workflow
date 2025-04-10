<?php

namespace App\Enums;

/**
 * Represents the available AI chat styles/personas.
 */
enum AiStyle: string
{
    case DEFAULT = "default";
    case CREATIVE = "creative";
    case PRECISE = "precise";
    case BALANCED = "balanced";

    /**
     * Get the user-friendly label for the style.
     * Mirrors the logic in AiStreamController::getAvailableStyles.
     */
    public function label(): string
    {
        return match ($this) {
            self::DEFAULT => "Default",
            self::CREATIVE => "Creative",
            self::PRECISE => "Precise",
            self::BALANCED => "Balanced",
        };
    }

    /**
     * Get the base system prompt associated with this style.
     * Mirrors the logic in AiStreamController::getSystemPromptForStyle (base part).
     * Note: The controller adds tool instructions separately.
     */
    public function getBaseSystemPrompt(): string
    {
        return match ($this) {
            self::DEFAULT
                => "You are a helpful Teacher Assistant. Provide clear and concise responses.",
            self::CREATIVE
                => "You are a creative Teacher Assistant. Think outside the box and provide imaginative responses.",
            self::PRECISE
                => "You are a precise Teacher Assistant. Focus on accuracy and factual information. Be concise and to the point.",
            self::BALANCED
                => "You are a balanced Teacher Assistant. Provide comprehensive yet accessible responses that balance detail with clarity.",
        };
    }

    /**
     * Get all available style options as an array for frontend dropdowns etc.
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
