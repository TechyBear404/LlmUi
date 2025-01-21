<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;



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

            logger()->info('Fetched models from API', [
                'response' => $response->json()
            ]);

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
                ->all();
        });
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string}|null $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    // public function sendMessage(
    //     ?array $messages = null,
    //     ?string $model = null,
    //     float $temperature = 0.7,
    //     bool $isTitle = false,
    //     Conversation $conversation = null
    // ): string {
    //     try {
    //         if ($conversation) {
    //             // Load messages if not provided
    //             if (!$messages) {
    //                 $messages = $conversation->messages()
    //                     ->orderBy('created_at')
    //                     ->get()
    //                     ->map(fn($msg) => [
    //                         'role' => $msg->role,
    //                         'content' => $msg->content,
    //                     ])
    //                     ->toArray();
    //             }

    //             // Include system prompt
    //             $systemPrompt = $isTitle
    //                 ? $this->getTitleSystemPrompt()
    //                 : $this->getChatSystemPrompt($conversation);

    //             array_unshift($messages, [
    //                 'role' => 'system',
    //                 'content' => $systemPrompt,
    //             ]);
    //         }

    //         $model = $model ?? ($conversation->model_id ?? self::DEFAULT_MODEL);


    //         // Send request
    //         $response = $this->client->chat()->create([
    //             'model' => $model,
    //             'messages' => $messages,
    //             'temperature' => $temperature,
    //         ]);

    //         logger()->info('API response:', ['response' => $response]);

    //         // Add response validation
    //         if (!isset($response['choices']) || empty($response['choices'])) {
    //             logger()->error('Invalid API response:', ['response' => $response]);
    //             throw new \Exception('Invalid response from AI service');
    //         }

    //         return $response['choices'][0]['message']['content'] ?? null;
    //     } catch (\Exception $e) {
    //         logger()->error('Chat service error:', [
    //             'error' => $e->getMessage(),
    //             'model' => $model,
    //             'conversation_id' => $conversation->id
    //         ]);
    //         throw $e;
    //     }
    // }
    public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
    {
        try {
            logger()->info('Envoi du message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (
                !$model || !$models->contains('id', $model)
            ) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];
            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            logger()->info('Réponse reçue:', ['response' => $response]);

            $content = $response->choices[0]->message->content;

            return $content;
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Undefined array key "choices"') {
                throw new \Exception("Limite de messages atteinte");
            }

            logger()->error('Erreur dans sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function makeTitle(Conversation $conversation = null): string
    {
        try {


            return $this->sendMessage($conversation, 0.7, true);
        } catch (\Exception $e) {
            logger()->error('Error in makeTitle:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Return a truncated version of the message if title generation fails
            return substr($conversation->messages()->last()->content, 0, 50) . '...';
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

    private function getChatSystemPrompt(?Conversation $conversation = null): string
    {
        $now = now()->locale('fr')->format('l d F Y H:i');
        $user = Auth::user();

        if ($conversation && $conversation->customInstruction) {
            $customInstruction = $conversation->customInstruction->getFormattedInstructions();

            // logger()->info('Using custom instruction for chat system prompt', [
            //     'conversationId' => $conversation->id,
            //     'customInstruction' => $customInstruction
            // ]);

            return $customInstruction;
        }

        return $this->getDefaultPrompt($user, $now);
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

    public function streamConversation(array $messages, ?string $model = null, float $temperature = 0.7)
    {
        try {
            logger()->info('Début streamConversation', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // Méthode "createStreamed" qui renvoie un flux "StreamResponse"
            return $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);
        } catch (\Exception $e) {
            logger()->error('Erreur dans streamConversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
