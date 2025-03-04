<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Gdn_Database;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model for tracked keywords.
 */
class KeywordsModel extends PipelineModel
{
    const TRACKED_KEYWORD_LIMIT = 100;

    private const KEYWORDS_TABLE_NAME = "keyword";

    private \Gdn_Session $session;

    /**
     * D.I.
     */
    public function __construct(\Gdn_Session $session)
    {
        $this->session = $session;

        parent::__construct(self::KEYWORDS_TABLE_NAME);

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $booleanProcessor = new BooleanFieldProcessor(["tracked"]);
        $this->addPipelineProcessor($booleanProcessor);
    }

    /**
     * Save a new keyword or update the tracked status if it already exists.
     *
     * @param string $keyword
     * @return int
     * @throws ClientException
     */
    public function saveOrUpdateKeyword(string $keyword): int
    {
        $allKeywords = $this->select(["tracked" => 1]);
        if (count($allKeywords) >= self::TRACKED_KEYWORD_LIMIT) {
            throw new ClientException(
                "You have reached the limit of " . self::TRACKED_KEYWORD_LIMIT . " tracked keywords",
                400
            );
        }

        $existingKeyword = $this->select(["keyword" => $keyword]);
        if (!empty($existingKeyword)) {
            $this->update(["tracked" => 1], ["keywordID" => $existingKeyword[0]["keywordID"]]);
            $trackedKeywordID = $existingKeyword[0]["keywordID"];
        } else {
            $trackedKeywordID = $this->insert([
                "keyword" => $keyword,
                "tracked" => 1,
            ]);
        }

        return $trackedKeywordID;
    }

    /**
     * Normalize the keyword.
     *
     * @param string $keyword
     * @return string
     */
    public function normalizeKeyword(string $keyword): string
    {
        return trim(strtolower($keyword));
    }

    /**
     * Get the tracked keywords output schema.
     *
     * @return Schema
     */
    public static function getOutputSchema(): Schema
    {
        $schema = Schema::parse([
            "keywordID:i",
            "keyword:s",
            "tracked:b",
            "occurrences:i?" => ["default" => 0],
            "averageSentiment:f?",
            "dateLastUsed:dt?",
        ]);

        return $schema;
    }

    /**
     * Create the tracked keywords table.
     *
     * @param Gdn_Database $database
     * @return void
     */
    public static function structure(Gdn_Database $database): void
    {
        $database
            ->structure()
            ->table(self::KEYWORDS_TABLE_NAME)
            ->primaryKey("keywordID", "int")
            ->column("keyword", "varchar(740)")
            ->column("tracked", "tinyint")
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->column("dateUpdated", "datetime", true)
            ->column("updateUserID", "int", true)
            ->set();

        $database
            ->structure()
            ->table(self::KEYWORDS_TABLE_NAME)
            ->createIndexIfNotExists("IX_keyword_keyword", ["keyword"])
            ->createIndexIfNotExists("IX_keyword_tracked", ["tracked"]);
    }
}
