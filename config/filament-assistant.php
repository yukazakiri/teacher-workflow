<?php

return [
    // Set the default chat driver class. You can override this in your local config.
    'chat_driver' => \AssistantEngine\Filament\Chat\Driver\DefaultChatDriver::class,
    'conversation_resolver' => \AssistantEngine\Filament\Chat\Resolvers\ConversationOptionResolver::class,
    'context_resolver' => \App\Filament\Chat\Resolvers\CustomContextResolver::class,
    'run_processor' => \AssistantEngine\Filament\Runs\Services\RunProcessorService::class,

    'default_run_queue' => env('DEFAULT_RUN_QUEUE', 'default'),
    'default_assistant' => env('DEFAULT_ASSISTANT_KEY', 'default'),

    // Assistants configuration: each assistance is identified by a key.
    // Each assistance has a name, a instruction, and a reference to an LLM connection.
    'assistants' => [
        // AI assistant configuration with key "default"
        'default' => [
            'name'              => 'TeacherHelper',
            'description'       => 'An AI assistant that helps teachers manage their classroom, students, and educational resources.',
            'instruction'       => 'You are TeacherHelper, an AI assistant designed specifically for educators to streamline their workflow and enhance their teaching experience.

Your primary functions include:

1. STUDENT MANAGEMENT:
   - Help teachers track student information, attendance, and performance
   - Provide insights on student progress and identify those who may need additional support
   - Assist with organizing students into groups for collaborative activities

2. TEAM COLLABORATION:
   - Help manage team members and their roles within the educational environment
   - Provide information about pending team invitations and team structure
   - Facilitate communication between team members

3. EDUCATIONAL RESOURCES:
   - Assist in organizing and finding teaching resources
   - Provide suggestions for lesson plans and activities based on curriculum requirements
   - Help teachers create and manage assessments, exams, and grading systems

4. CLASSROOM MANAGEMENT:
   - Offer strategies for effective classroom management
   - Help schedule and organize classroom activities
   - Assist with time management for lessons and curriculum planning

When responding to queries:
- Be concise and practical in your responses
- Prioritize actionable advice that teachers can implement immediately
- Consider the context of the current team, students, and educational setting
- Respect privacy and confidentiality of student information
- Provide evidence-based teaching strategies when appropriate
- Use a supportive and encouraging tone
- Format responses in clean, well-structured markdown with proper headings, tables, and formatting
- Use visual elements like progress bars and emojis when appropriate to enhance readability

Remember that you have access to information about the current user, their team, team members, students, and pending invitations. Use this context to provide personalized and relevant assistance.

You can also use the educational_context tool to retrieve specific information about students, team statistics, and progress data when needed. All responses from this tool are formatted in markdown with clear headings, tables, and visual elements for improved readability.',
            'llm_connection'    => 'openai', // This should correspond to an entry in the llm_connections section.
            'model'             => 'gpt-4o-mini',
            'registry_meta_mode' => false,
            // List the tool identifiers to load for this assistant.
            'tools'             => ['educational_context']
        ],
    ],

    // LLM Connections configuration: each connection is identified by an identifier.
    // Each connection must include an URL and an API key.
    'llm_connections' => [
        // Example LLM connection configuration with identifier "openai"
        'openai' => [
            'url'     => 'https://api.openai.com/v1/',
            'api_key' => env('OPEN_AI_KEY'),
        ]
    ],

    // Registry configuration
    'registry' => [
        'description' => 'Registry where you can control active functions.',
        'presenter'   => function ($registry) {
            // This closure receives the open function registry as a parameter.
            // You can customize how the registry is "presented" here.
            return new \AssistantEngine\OpenFunctions\Core\Presenter\RegistryPresenter($registry);
        },
    ],

    // Tools configuration: each tool is identified by a key.
    'tools' => [
        'educational_context' => [
            'namespace'   => 'educational_context',
            'description' => 'Functions to retrieve detailed information about students, team members, and educational progress.',
            'tool'        => function () {
                return new \App\Filament\Chat\Tools\EducationalContextTool();
            },
        ],
        'weather' => [
            'namespace'   => 'weather',
            'description' => 'Function to get informations about the weather.',
            'tool'        => function () {
                return new \AssistantEngine\OpenFunctions\Core\Examples\WeatherOpenFunction();
            },
        ],
        'pizza' => [
            'namespace'   => 'pizza',
            'description' => 'This is a nice pizza place',
            'tool'        => function () {
                $pizza = [
                    'Margherita',
                    'Pepperoni',
                    'Hawaiian',
                    'Veggie',
                    'BBQ Chicken',
                    'Meat Lovers'
                ];
                return new \AssistantEngine\OpenFunctions\Core\Examples\DeliveryOpenFunction($pizza);
            },
        ],
        'burger' => [
            'namespace'   => 'burger',
            'description' => 'This is a nice burger place',
            'tool'        => function () {

                $burgers = [
                    'Classic Burger',
                    'Cheese Burger',
                    'Bacon Burger',
                    'Veggie Burger',
                    'Double Burger'
                ];
                return new \AssistantEngine\OpenFunctions\Core\Examples\DeliveryOpenFunction($burgers);
            },
        ],
    ],

    'button' => [
        'show' => true,
        'options' => [
            'label' => 'FilaAI',
            'size' => \Filament\Support\Enums\ActionSize::ExtraLarge,
            'color' => \Filament\Support\Colors\Color::Purple,
            'icon' => 'heroicon-o-chat-bubble-bottom-center-text'
        ]
    ],

    // Sidebar configuration
    'sidebar' => [
        // Whether the sidebar is enabled
        'enabled' => true,
        // If set to true, the sidebar will be open by default on load.
        // Using 'open_by_default' instead of 'auto_visible'
        'open_by_default' => false,
        // The width of the sidebar, defined as a CSS dimension.
        // must be an integer
        'width' => 700,
    ],
];
