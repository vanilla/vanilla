<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\OpenAI;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponseException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Client used to make calls to OpenAI.
 */
class OpenAIClient
{
    const MODELS = [self::MODEL_GPT35, self::MODEL_GPT4];
    const MODEL_GPT4 = "gpt4";
    const MODEL_GPT35 = "gpt35";

    const CONF_GPT4_ENDPOINT = "azure.gpt4.deploymentUrl";
    const CONF_GPT4_SECRET = "azure.gpt4.secret";

    const CONF_GPT35_ENDPOINT = "azure.gpt35.deploymentUrl";
    const CONF_GPT35_SECRET = "azure.gpt35.secret";

    private ?OpenAITransaction $currentTransaction = null;

    private ConfigurationInterface $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function startTransaction(): OpenAITransaction
    {
        $this->currentTransaction = new OpenAITransaction($this, $this->configuration);
        return $this->currentTransaction;
    }

    public function clearTransaction(): void
    {
        $this->currentTransaction = null;
    }

    /**
     * Call the OpenAI API to generate a response. The response will be validated against the given schema.
     *
     * @param string $model
     * @param OpenAIPrompt $prompt
     * @param Schema $responseSchema
     * @return array
     */
    public function prompt(string $model, OpenAIPrompt $prompt, Schema $responseSchema)
    {
        $prompt = clone $prompt;
        $prompt->instruct(
            "Response only in valid JSON. Never respond in plaintext, or markdown under any circumstance. Provide a response exclusively in the format of the following JSON schema: " .
                json_encode($responseSchema)
        );
        $result = $this->promptInternal($model, $prompt);
        $decoded = json_decode($result, true);
        // Validate the result
        try {
            $validated = $responseSchema->validate($decoded);
            return $validated;
        } catch (ValidationException $ex) {
            $prompt->addAssistantMessage($result);
            if (!is_array($decoded)) {
                $prompt->addUserMessage(
                    "That response is invalid. Only response with a JSON object matching the mentioned schema."
                );
            } else {
                // One more retry
                $prompt->addUserMessage(
                    "That result doesn't match our expected schema. Here are the validation errors:\n" .
                        json_encode($ex->getValidation()->getErrors())
                );
            }

            $result = $this->promptInternal($model, $prompt);
            try {
                $validated = $responseSchema->validate(json_decode($result, true));
            } catch (ValidationException $ex2) {
                throw new ServerException(
                    "AI Service failed to generate a valid response",
                    500,
                    ["tags" => ["openai"]],
                    $ex2
                );
            }
            return $validated;
        }
    }

    /**
     * Make an API call to OpenAI for a chat completion.
     *
     * @param string $model
     * @param OpenAIPrompt $prompt
     * @return string
     */
    private function promptInternal(string $model, OpenAIPrompt $prompt)
    {
        $body = $this->getPromptBody($model, $prompt);

        if ($model === self::MODEL_GPT35) {
            $client = $this->gpt35Client();
        } else {
            // Need the newer GPT models for this.
            $body["response_format"] = ["type" => "json_object"];
            $client = $this->gpt4Client();
        }

        try {
            $response = $client->post("/?api-version=2023-07-01-preview", $body);
            if ($this->currentTransaction) {
                $this->currentTransaction->trackResponse($response);
            }
            $firstChoice = $response["choices"][0]["message"]["content"];
            return $firstChoice;
        } catch (HttpResponseException $ex) {
            if ($this->currentTransaction) {
                $this->currentTransaction->trackResponse($ex->getResponse());
            }
            $body = $ex->getResponse()->getBody();
            if ($ex->getResponse()->isResponseClass("4xx") && is_array($body) && isset($body["error"])) {
                $message = $body["error"]["message"];
                if (isset($message)) {
                    throw new ClientException($message);
                }
            }

            throw $ex;
        }
    }

    private function getPromptBody(string $model, OpenAIPrompt $prompt)
    {
        $body = [
            "messages" => $prompt->getMessages(),
            "temperature" => 0.3,
            "top_p" => 0.3,
            "frequency_penalty" => 0.2,
            "presence_penalty" => 0.35,
            "max_tokens" => 400,
        ];

        if ($model === self::MODEL_GPT4) {
            // Need the newer GPT models for this.
            $body["response_format"] = ["type" => "json_object"];
        }
        return $body;
    }

    public function getPromptDebugJson(string $model, OpenAIPrompt $prompt, Schema $schema): string
    {
        $prompt = clone $prompt;
        $prompt->instruct(
            "Response only in valid JSON. Provide a response exclusively in the format of the following JSON schema: " .
                json_encode($schema)
        );
        $body = $this->getPromptBody($model, $prompt);
        return json_encode($body, JSON_PRETTY_PRINT);
    }

    ///
    /// Utilities
    ///

    /**
     * Get an authenticated GPT-3.5 client.
     *
     * @return HttpClient
     */
    private function gpt35Client(): HttpClient
    {
        $baseUrl = $this->configuration->get(self::CONF_GPT35_ENDPOINT);
        $secret = $this->configuration->get(self::CONF_GPT35_SECRET);
        return $this->gptClient($baseUrl, $secret);
    }

    /**
     * Get an authenticated GPT-4 client.
     *
     * @return HttpClient
     */
    private function gpt4Client(): HttpClient
    {
        $baseUrl = $this->configuration->get(self::CONF_GPT4_ENDPOINT);
        $secret = $this->configuration->get(self::CONF_GPT4_SECRET);
        return $this->gptClient($baseUrl, $secret);
    }

    /**
     * Create an http client with certain credentials.
     *
     * @param string|null $baseUrl
     * @param string|null $secret
     * @return HttpClient
     */
    private function gptClient(?string $baseUrl, ?string $secret): HttpClient
    {
        if (empty($baseUrl)) {
            throw new ServerException("Missing baseUrl for azure openAI client.");
        }

        if (empty($secret)) {
            throw new ServerException("Missing secret for azure openAI client.");
        }

        $client = new HttpClient($baseUrl);
        $client->setThrowExceptions(true);

        $client->setDefaultHeaders([
            "content-type" => "application/json",
            "api-key" => $secret,
            "user-agent" => "vanilla-forums-ai/1.0",
        ]);
        return $client;
    }
}
