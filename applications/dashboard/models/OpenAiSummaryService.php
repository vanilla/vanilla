<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Gdn;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;

/**
 * Retrieval-Augmented Generation summary service.
 *
 * Used Open IA configuration for AI conversation summary.
 */
class OpenAiSummaryService
{
    use LoggerAwareTrait;

    /**
     * OpenAI Summary constructor.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function __construct(private ConfigurationInterface $config, private OpenAIClient $openAIClient)
    {
        $this->logger = Gdn::getContainer()->get(LoggerInterface::class);
    }

    /**
     * Check if OpenAI summary is enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Gdn::config("Feature.aiFeatures.Enabled");
    }

    /**
     * Get the base prompt for querying OpenAI for AI conversation summary.
     *
     * @param string $searchQuery
     *
     * @return OpenAIPrompt
     */
    public static function getBasePrompt(string $searchQuery): OpenAIPrompt
    {
        $prompt = OpenAIPrompt::create()->instruct(
            <<<PROMPT
You are an AI assistant that provides short summary from provided collection of text in format ["summary"=> ["", ""]], to answer query [$searchQuery}.  Only use provided text for answers.

PROMPT
        );

        return $prompt;
    }

    /**
     * Return a summary based on user search query and search fragments results.
     *
     * @param array $text
     * @param string $searchQuery
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public function getSummary(array $text, string $searchQuery): array
    {
        $ragsSchema = OpenAiSummaryService::getRagsSchema();
        $prompt = OpenAiSummaryService::getBasePrompt($searchQuery);
        $prompt->addUserMessage($text);
        $summary = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT4, $prompt, $ragsSchema);
        $result["summary"] = $summary["ragSummary"]["summary"][0];
        return $result;
    }

    /**
     * Get the schema for storing the answer response from OpenAI.
     *
     * @return Schema
     */
    public static function getRagsSchema(): Schema
    {
        return Schema::parse(["ragSummary:o"]);
    }
}
