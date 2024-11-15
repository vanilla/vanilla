<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures\OpenAI;

use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;

class MockOpenAIClient extends OpenAIClient
{
    protected array $mockResponses;

    /**
     * Return mocked responses from OpenAI.
     *
     * {@inheritDoc}
     */
    public function prompt(string $model, $prompt, $responseSchema)
    {
        return $this->lookupMockResponse($prompt);
    }

    /**
     * Add a mock response based on regex pattern in prompt.
     *
     * @param string $pattern
     * @param array $mockResponse
     * @return void
     */
    public function addMockResponse(string $pattern, array $mockResponse)
    {
        $this->mockResponses[$pattern] = $mockResponse;
    }

    /**
     * Return mocked response.
     *
     * @param OpenAIPrompt $prompt
     * @return array
     */
    private function lookupMockResponse(OpenAIPrompt $prompt): array
    {
        $messages = $prompt->getMessages();
        foreach ($messages as $message) {
            foreach ($this->mockResponses as $pattern => $response) {
                if (preg_match($pattern, $message["content"])) {
                    return $response;
                }
            }
        }
        return [];
    }
}
