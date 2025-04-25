<?php

namespace NeuronAI\Providers\Ollama;

use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\Usage;
use NeuronAI\Exceptions\ProviderException;

trait HandleChat
{
    public function chat(array $messages): Message
    {
        // Include the system prompt
        if (isset($this->system)) {
            \array_unshift($messages, new Message(Message::ROLE_SYSTEM, $this->system));
        }

        $json = [
            'stream' => false,
            'model' => $this->model,
            'messages' => $this->messageMapper()->map($messages),
            ...$this->parameters,
        ];

        if (! empty($this->tools)) {
            $json['tools'] = $this->generateToolsPayload();
        }

        foreach ($json['tools'] as $idx => $tool) {
            if (!array_key_exists('parameters', $tool['function'])) {
                $json['tools'][$idx]['function']['parameters'] = [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                    'required' => [],
                ];
            }
        }

        try {
            $response = $this->client->post('chat', compact('json'));
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            throw new ProviderException("Ollama chat error: {$ex->getMessage()}");
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new ProviderException("Ollama chat error: {$response->getBody()->getContents()}");
        }

        $response = json_decode($response->getBody()->getContents(), true);
        $message = $response['message'];

        if (\array_key_exists('tool_calls', $message)) {
            $message = $this->createToolMessage($message);
        } else {
            $message = new AssistantMessage($message['content']);
        }

        if (\array_key_exists('prompt_eval_count', $response) && \array_key_exists('eval_count', $response)) {
            $message->setUsage(
                new Usage($response['prompt_eval_count'], $response['eval_count'])
            );
        }

        return $message;
    }
}
