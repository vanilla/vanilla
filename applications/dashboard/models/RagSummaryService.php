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
use MachineTranslation\Services\CommunityMachineTranslationServiceInterface;
use MachineTranslation\Services\GptTranslationService;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;
use Vanilla\Search\SearchResultItem;

/**
 * Retrieval-Augmented Generation summary service.
 *
 * Used Open IA configuration for RAG summary.
 */
class RagSummaryService
{
    use LoggerAwareTrait;

    public const CONFIG_KEY = "Feature.rAGSummary.enabled";

    /**
     * RAG Summary constructor.
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
     * Check if RAG summary is enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Gdn::config("Feature.aiFeatures.Enabled") && Gdn::config(RagSummaryService::CONFIG_KEY);
    }

    /**
     * Get the base prompt for querying OpenAI for rag summary.
     *
     * @param string $searchQuery
     *
     * @return OpenAIPrompt
     * @throws ClientException
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
     * Return RAG summary based on user search query and search chunk results.
     *
     * @param array $texts [Key => Value]
     * @param string $searchQuery
     * @param bool $summarize
     *
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public function ragSummary(array $searchResults, string $searchQuery, bool $summarize = false): array
    {
        $texts = ["summary" => []];
        foreach ($searchResults as $searchResult) {
            if ($searchResult instanceof SearchResultItem) {
                $texts["summary"][] = $searchResult->getChunks();
            } else {
                $texts["summary"][] = $searchResult["chunkText"];
            }
        }

        $result = [
            "excerpt" => implode(" ", $texts["summary"]),
            "title" => $searchQuery,
            "id" => "",
            "type" => "",
            "url" => "",
        ];
        if ($summarize && $this->isEnabled()) {
            $ragsSchema = RagSummaryService::getRagsSchema();
            $prompt = RagSummaryService::getBasePrompt($searchQuery);
            $prompt->addUserMessage($texts);
            $summary = $this->openAIClient->prompt(OpenAIClient::MODEL_GPT4OMINI, $prompt, $ragsSchema);
            $result["summary"] = $summary["ragSummary"]["summary"][0];
        }

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
