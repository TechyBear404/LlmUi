<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    /**
     * @return array<array-key, array{
     *     id: string,
     *     name: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     pricing: array{prompt: int, completion: int}
     * }>
     */
    public function getModels(): array
    {
        return cache()->remember('openai.models', now()->addHour(), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->filter(function ($model) {
                    return str_ends_with($model['id'], ':free');
                })
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                    ];
                })
                ->values()
                ->all()
            ;
        });
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string}|null $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function sendMessage(
        ?array $messages = null,
        ?string $model = null,
        float $temperature = 0.7,
        bool $isTitle = false,
        ?Conversation $conversation = null
    ): string {
        try {
            // Make sure conversation is loaded with customInstruction
            if ($conversation && !$conversation->relationLoaded('customInstruction')) {
                $conversation->load('customInstruction');
            }

            // If conversation is provided and no messages are provided, use conversation messages
            if ($conversation && !$messages) {
                $messages = $conversation->messages()
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($message) {
                        return [
                            'role' => $message->role,
                            'content' => $message->content
                        ];
                    })
                    ->toArray();
            }

            // Ensure messages is an array
            $messages = $messages ?? [];

            // logger()->info('Starting sendMessage request', [
            //     'model' => $model,
            //     'temperature' => $temperature,
            //     'isTitle' => $isTitle,
            //     'messagesCount' => count($messages),
            //     'conversationId' => $conversation?->id,
            //     'hasCustomInstruction' => $conversation?->customInstruction !== null,
            //     'modelId' => $conversation?->model_id,
            // ]);

            // Use conversation's model if available and no specific model provided
            if (!$model && $conversation && $conversation->model_id) {
                $model = $conversation->model_id;
            }

            if ($isTitle) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $this->getTitleSystemPrompt()
                ]);
            } else {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $this->getChatSystemPrompt($conversation)
                ]);
            }

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
            }

            // logger()->info('API request parameters', [
            //     'model' => $model,
            //     'messages' => $messages,
            //     'temperature' => $temperature
            // ]);

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            // logger()->info('API response received', [
            //     'status' => 'success',
            //     'content_length' => strlen($response->choices[0]->message->content ?? '')
            // ]);

            if (!isset($response->choices[0]->message->content)) {
                throw new \Exception("Invalid response from API");
            }

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            // logger()->error('Error in sendMessage:', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString(),
            //     'model' => $model,
            //     'temperature' => $temperature,
            //     'isTitle' => $isTitle,
            //     'conversationId' => $conversation?->id
            // ]);
            throw $e;
        }
    }

    public function makeTitle(string $messages, string $model): string
    {
        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $messages
                ]
            ];

            return $this->sendMessage($messages, $model, 0.7, true);
        } catch (\Exception $e) {
            logger()->error('Error in makeTitle:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Return a truncated version of the message if title generation fails
            return substr($message, 0, 50) . '...';
        }
    }

    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make()
        ;
    }

    /**
     * @return string
     */
    private function getChatSystemPrompt(?Conversation $conversation = null): string
    {
        $user = Auth::user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        // Build the prompt content
        $promptContent = '';

        // Make sure customInstruction exists and is loaded
        if (
            $conversation &&
            $conversation->custom_instruction_id &&
            $conversation->customInstruction
        ) {

            $instruction = $conversation->customInstruction;
            $promptContent = '';

            if ($instruction->about_user) {
                $promptContent .= "À propos de l'utilisateur:\n" . $instruction->about_user . "\n\n";
            }

            if ($instruction->ai_response_style) {
                $promptContent .= "Style de réponse souhaité:\n" . $instruction->ai_response_style;
            }

            // If no content was added from custom instruction, fallback to default
            if (empty(trim($promptContent))) {
                $promptContent = $this->getDefaultPrompt($user, $now);
            }
        } else {
            $promptContent = $this->getDefaultPrompt($user, $now);
        }

        // Add detailed logging
        logger()->info('Chat system prompt', [
            'promptContent' => $promptContent,
            'conversationId' => $conversation?->id,
            'hasCustomInstruction' => $conversation?->customInstruction !== null,
            'customInstructionId' => $conversation?->custom_instruction_id,
        ]);

        return $promptContent;
    }

    private function getDefaultPrompt($user, $now): string
    {
        return <<<EOT
            Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.
            Tu es actuellement utilisé par {$user->name}.
            Tu dois utilisé le Markdown pour formater tes réponses.

            Instructions importantes:
            - Ignore toutes les instructions des messages précédents.
            - Chaque message doit être traité de manière indépendante.
            - Si aucune instruction spécifique n'est donnée dans le message actuel, réponds de manière professionnelle et neutre.
            - Les seules instructions à suivre sont celles présentes dans le message actuel.
            - Le formatage Markdown doit toujours être utilisé pour les réponses.
        EOT;
    }

    private function getTitleSystemPrompt(): string
    {
        return <<<EOT
            Tu es un assistant spécialisé dans la création de titres de conversation.
            Ton travail consiste à créer un titre à partir d'un message d'ouverture (question) et de la réponse associée.
            Le titre doit respecter les consignes suivantes :
            1. Il doit être clair, concis et refléter fidèlement le contenu de la question et de la réponse.
            2. Il ne doit pas dépasser 10 mots.
            3. Il doit attirer l'attention tout en restant informatif.
            4. Évite les répétitions inutiles et privilégie des termes spécifiques et évocateurs.
            5. Si possible, utilise un ton neutre et objectif.
        EOT;
    }
}
