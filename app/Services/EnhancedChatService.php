<?php

namespace App\Services;

use LangChain\LLMs\OpenRouter;
use LangChain\Memory\VectorStoreRetrieverMemory;
use LangChain\Documents\TextLoader;
use LangChain\VectorStores\PostgresVector;
use LangChain\Chains\ConversationalRetrievalChain;

class EnhancedChatService extends ChatService
{
    private $vectorStore;
    private $memory;

    public function __construct()
    {
        parent::__construct();
        $this->initVectorStore();
    }

    private function initVectorStore()
    {
        $this->vectorStore = new PostgresVector(
            'message_embeddings',
            1536
        );

        $this->memory = new VectorStoreRetrieverMemory([
            'vectorStoreRetriever' => $this->vectorStore->asRetriever()
        ]);
    }

    public function sendMessage(array $messages = null, string $model = null, float $temperature = 0.7, bool $isTitle = false, Conversation $conversation = null): string
    {
        $llm = new OpenRouter([
            'apiKey' => $this->apiKey,
            'baseUrl' => $this->baseUrl,
            'modelName' => $model ?? self::DEFAULT_MODEL
        ]);

        $chain = new ConversationalRetrievalChain([
            'llm' => $llm,
            'retriever' => $this->vectorStore->asRetriever(),
            'memory' => $this->memory
        ]);

        $response = $chain->run([
            'question' => end($messages)['content'],
            'chat_history' => $this->formatChatHistory($messages)
        ]);

        return $response;
    }

    public function processFile($file)
    {
        $loader = match (strtolower($file->getClientOriginalExtension())) {
            'pdf' => new PDFLoader($file->getRealPath()),
            'docx' => new DocxLoader($file->getRealPath()),
            default => new TextLoader($file->getRealPath())
        };

        $docs = $loader->load();
        $this->vectorStore->addDocuments($docs);

        return true;
    }

    private function formatChatHistory($messages): string
    {
        return collect($messages)
            ->map(fn($msg) => "{$msg['role']}: {$msg['content']}")
            ->join("\n");
    }
}
