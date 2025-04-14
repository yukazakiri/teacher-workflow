# Prism: Unified LLM Integration for Laravel

## Introduction

Large Language Models (LLMs) have revolutionized how we interact with artificial intelligence, enabling applications to understand, generate, and manipulate human language with unprecedented sophistication. These powerful models open up exciting possibilities for developers, from creating chatbots and content generators to building complex AI-driven applications.

Prism **simplifies the process of integrating LLMs into your Laravel projects**, providing a unified interface to work with various AI providers. This allows you to focus on crafting innovative AI features for your users, rather than getting bogged down in the intricacies of different APIs and implementation details.

Prism draws significant inspiration from the Vercel AI SDK, adapting its powerful concepts and developer-friendly approach to the Laravel ecosystem.

## Quick Example

Here's a quick example of how you can generate text using Prism with different providers:

```php
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider; // Use the official enum

// Example using OpenAI
$response = Prism::text()
    ->using(Provider::OpenAI, 'gpt-4') // Specify provider and model
    ->withSystemPrompt('You are a helpful assistant.') // Optional system prompt
    ->withPrompt('Explain quantum computing to a 5-year-old.') // User prompt
    ->asText(); // Execute and get response as text

echo $response->text;

// Example using Anthropic
$response = Prism::text()
    ->using(Provider::Anthropic, 'claude-3-sonnet')
    ->withSystemPrompt('You are a helpful assistant.')
    ->withPrompt('Explain quantum computing to a 5-year-old.')
    ->asText();

echo $response->text;

// ... similar examples for Mistral, Ollama etc.
```

## Key Features

*   **Unified Provider Interface**: Switch seamlessly between AI providers like OpenAI, Anthropic, Groq, Mistral, Ollama and more without changing your application code.
*   **Tool System**: Extend AI capabilities by defining custom tools that the AI can use to interact with your application's business logic and data sources.
*   **Image Support**: Work with multi-modal models that can process both text and images (check provider/model support).
*   **Streaming Support**: Easily stream responses back to the client for real-time interactions.
*   **Structured Output**: Request responses formatted according to specific schemas (e.g., JSON).
*   **Embeddings**: Generate vector embeddings for text data.

## Helper Function

Prism also provides a fluent `prism()` helper function to resolve the `Prism` instance from the application container for concise syntax:

```php
use Prism\Prism\Enums\Provider;

$response = prism() // Use the helper
    ->text()
    ->using(Provider::OpenAI, 'gpt-4')
    ->withPrompt('Explain quantum computing to a 5-year-old.')
    ->asText();

echo $response->text;
```

## Supported Providers

Prism currently offers first-party support for these leading AI providers:

*   Anthropic
*   DeepSeek
*   Groq
*   Mistral
*   Ollama
*   OpenAI
*   xAI

*(Support for providers like Amazon Bedrock, Azure OpenAI, Gemini, and VoyageAI may vary or be planned - refer to the official Prism documentation for the latest status).*

Each provider brings its own strengths, and Prism makes it easy to leverage them through a consistent, elegant interface.

## Provider Feature Support

Feature support (like streaming, tool usage, image input) can vary between providers and even specific models within a provider.

**Always check the dedicated provider pages in the official Prism documentation for detailed considerations, limitations, and available options.** Model-specific features should be confirmed with the AI provider's documentation.

| Provider        | Text | Streaming | Structured | Embeddings | Image | Tools | Documents |
| :-------------- | :--: | :-------: | :--------: | :--------: | :---: | :---: | :-------: |
| Amazon Bedrock  |      |           |            |            |       |       |           |
| Anthropic       | ✅   | ✅        | ✅         |            | ✅    | ✅    |           |
| Azure OpenAI    | ✅   | ✅        | ✅         | ✅         | ✅    | ✅    |           |
| DeepSeek        | ✅   | ✅        |            | ✅         |       | ✅    |           |
| Gemini          | ✅   | ✅        | ✅         | ✅         | ✅    | ✅    |           |
| Groq            | ✅   | ✅        | ✅         |            |       | ✅    |           |
| Mistral         | ✅   | ✅        | ✅         | ✅         |       | ✅    |           |
| Ollama          | ✅   | ✅        | ✅         | ✅         | ✅    | ✅    | ✅        |
| OpenAI          | ✅   | ✅        | ✅         | ✅         | ✅    | ✅    |           |
| VoyageAI        |      |           |            | ✅         |       |       |           |
| xAI             | ✅   | ✅        |            |            |       |       |           |

*(Table interpretation based on common LLM features and likely support, **refer to official Prism docs for accuracy**)*
*   ✅: Supported
*   *(Blank)*: Check Docs / Likely Unsupported / Not Applicable

---

Released under the MIT License.
Copyright © 2024-present TJ Miller
