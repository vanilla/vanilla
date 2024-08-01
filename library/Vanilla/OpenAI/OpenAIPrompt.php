<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\OpenAI;

use Garden\Schema\Schema;

/**
 * Class for constructing a chat completion prompt for OpenAI.
 */
class OpenAIPrompt
{
    private const ROLE_SYSTEM = "system";
    private const ROLE_USER = "user";
    private const ROLE_ASSISTANT = "assistant";

    /**
     * @var array<array{question: string, answer: string}>
     */
    private array $examples = [];

    /** @var string System prompt passed to OpenAI */
    private string $systemPrompt = "You are a helpful assistant designed to output JSON.";

    /** @var array[] */
    private array $messages = [];

    /**
     * @return self
     */
    public static function create(): self
    {
        return new OpenAIPrompt();
    }

    /**
     * Append messages from another OpenAI prompt.
     *
     * @param OpenAIPrompt $source
     * @return $this
     */
    public function appendPromptMessages(OpenAIPrompt $source): OpenAIPrompt
    {
        $this->messages = array_merge($this->messages, $source->messages);
        return $this;
    }

    /**
     * Get all messages in the prompt including the system one.
     *
     * @return array[]
     */
    public function getMessages(): array
    {
        $messages = [
            [
                "role" => self::ROLE_SYSTEM,
                "content" => $this->systemPrompt,
            ],
        ];
        foreach ($this->examples as $example) {
            $messages[] = [
                "role" => self::ROLE_USER,
                "content" => $example["question"],
            ];
            $messages[] = [
                "role" => self::ROLE_ASSISTANT,
                "content" => $example["answer"],
            ];
        }

        foreach ($this->messages as $message) {
            $messages[] = $message;
        }
        return $messages;
    }

    /**
     * Add the system prompt
     *
     * @param string $systemInstruction
     * @return $this
     */
    public function instruct(string $systemInstruction): OpenAIPrompt
    {
        $this->systemPrompt = trim($this->systemPrompt) . "\n" . $systemInstruction;
        return $this;
    }

    /**
     * @param string $question
     * @param string|array $answer
     * @return $this
     */
    public function addExample(string $question, $answer): OpenAIPrompt
    {
        $this->examples[] = [
            "question" => $question,
            "answer" => is_array($answer) ? json_encode($answer) : $answer,
        ];
        return $this;
    }

    /**
     * Add a message from the assistant.
     *
     * @param string|array $content
     * @return OpenAIPrompt
     */
    public function addAssistantMessage($content): OpenAIPrompt
    {
        return $this->addMessage(self::ROLE_ASSISTANT, $content);
    }

    /**
     * Add a message from the user.
     *
     * @param string|array $content
     * @return OpenAIPrompt
     */
    public function addUserMessage($content): OpenAIPrompt
    {
        return $this->addMessage(self::ROLE_USER, $content);
    }

    /**
     * @param string $role
     * @param string|array $content
     * @return OpenAIPrompt
     */
    private function addMessage(string $role, $content): OpenAIPrompt
    {
        $this->messages[] = [
            "role" => $role,
            "content" => !is_string($content) ? json_encode($content) : $content,
        ];
        return $this;
    }
}
