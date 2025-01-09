<?php

namespace App\Services;

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
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function sendMessage(array $messages, ?string $model = null, float $temperature = 0.7, bool $isTitle = false): string
    {
        try {
            logger()->info('Starting sendMessage request', [
                'model' => $model,
                'temperature' => $temperature,
                'isTitle' => $isTitle,
                'messagesCount' => count($messages)
            ]);

            if ($isTitle) {
                array_unshift($messages, $this->getTitleSystemPrompt());
            } else {
                array_unshift($messages, $this->getChatSystemPrompt());
            }

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
            }

            logger()->info('API request parameters', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature
            ]);

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            logger()->info('API response received', [
                'status' => 'success',
                'content_length' => strlen($response->choices[0]->message->content ?? '')
            ]);

            if (!isset($response->choices[0]->message->content)) {
                throw new \Exception("Invalid response from API");
            }

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            logger()->error('Error in sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $model,
                'temperature' => $temperature,
                'isTitle' => $isTitle
            ]);
            throw $e;
        }
    }

    public function makeTitle(string $message, string $model): string
    {
        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $message
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
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = Auth::user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        return [
            'role' => 'system',
            'content' => <<<EOT
                Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.
                Tu es actuellement utilisé par {$user->name}.
                Tu dois utilisé le Markdown pour formater tes réponses.
                EOT,
        ];
    }

    private function getTitleSystemPrompt(): array
    {
        return [
            'role' => 'system',
            'content' => <<<EOT
                Tu es un assistant
                Ton travail est de crée des titre de conversation a partir de message d'ouverture
                Le titre doit etre clair et concis
                Le titre ne doit pas depasser 10 mots
                EOT,
        ];
    }
}
