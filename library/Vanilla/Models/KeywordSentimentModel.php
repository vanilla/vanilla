<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Gdn_Database;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\KeywordsModel;
use Vanilla\Models\PipelineModel;
use Vanilla\Schema\RangeExpression;

/**
 * Model for keyword sentiment table.
 */
class KeywordSentimentModel extends PipelineModel
{
    private const KEYWORD_SENTIMENT_TABLE_NAME = "keywordSentiment";

    private const DEFAULT_PRUNE_PERIOD = "2 months";

    private const PRUNE_CONFIG_KEY = "SentimentAnalysis.KeywordSentiment.PruningPeriod";

    private KeywordsModel $keywordsModel;

    /**
     * Constructor.
     *
     * @param KeywordsModel $keywordsModel
     * @param ConfigurationInterface $config
     */
    public function __construct(KeywordsModel $keywordsModel, ConfigurationInterface $config)
    {
        parent::__construct(self::KEYWORD_SENTIMENT_TABLE_NAME);

        $this->keywordsModel = $keywordsModel;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);
        $prunePeriod = $config->get(self::PRUNE_CONFIG_KEY, self::DEFAULT_PRUNE_PERIOD);
        $pruneProcessor = new PruneProcessor("dateInserted", $prunePeriod);
        $this->addPipelineProcessor($pruneProcessor);
    }

    /**
     * @inheritDoc
     */
    public function insert(array $set, $options = []): int
    {
        // Check to see if the keyword is already in our keywords table.
        $existingKeyword = $this->keywordsModel->select(["keyword" => $set["keyword"]]);

        // If it's not, insert it and use the returned keywordID. Otherwise, use the existing keywordID.
        if (empty($existingKeyword)) {
            $tracked = $set["tracked"] ?? false;
            $keywordID = $this->keywordsModel->insert(["keyword" => $set["keyword"], "tracked" => $tracked]);
        } else {
            $keywordID = $existingKeyword[0]["keywordID"];
        }

        $set["keywordID"] = $keywordID;
        unset($set["keyword"]);
        parent::insert($set);

        return $keywordID;
    }

    /**
     * Get keywords with fields joined from the keywordSentiment table.
     *
     * @param $where
     * @param $orderField
     * @param $orderDirection
     * @param $limit
     * @param $offset
     * @param $having
     * @return array
     */
    public function getKeywords(
        array $where,
        ?string $orderField = "occurrences",
        ?string $orderDirection = "desc",
        ?int $limit = 50,
        ?int $offset = 0,
        ?array $havings = []
    ): array {
        $query = $this->database
            ->sql()
            ->select(
                "k.keywordID, k.keyword, k.tracked, sum(ks.occurrences) as occurrences, avg(ks.sentimentScore) as averageSentiment, k.dateInserted, k.insertUserID, max(ks.dateInserted) as dateLastUsed"
            )
            ->from("keyword k")
            ->leftJoin("keywordSentiment ks", "k.keywordID = ks.keywordID")
            ->where($where)
            ->orderBy($orderField, $orderDirection)
            ->groupBy("k.keywordID")
            ->limit($limit)
            ->offset($offset);

        $this->addHavingsToQuery($query, $havings);

        $keywords = $query->get()->resultArray();

        return $keywords;
    }

    /**
     * Get the total count of keywords for a given query.
     *
     * @param array $where
     * @param array $havings
     * @return int
     */
    public function getKeywordsCount(array $where, array $havings): int
    {
        $query = $this->database
            ->sql()
            ->select(
                "k.keywordID, k.keyword, k.tracked, sum(ks.occurrences) as occurrences, avg(ks.sentimentScore) as averageSentiment, k.dateInserted, k.insertUserID, max(ks.dateInserted) as dateLastUsed"
            )
            ->from("keyword k")
            ->leftJoin("keywordSentiment ks", "k.keywordID = ks.keywordID")
            ->where($where)
            ->groupBy("k.keywordID");

        $this->addHavingsToQuery($query, $havings);

        $count = $query->get()->count();
        return $count;
    }

    /**
     * Add having clauses to a kewyword query.
     *
     * @param \Gdn_MySQLDriver $query
     * @param $havings
     * @return void
     */
    private function addHavingsToQuery(\Gdn_MySQLDriver $query, $havings): void
    {
        if (!empty($havings)) {
            $query->beginWhereGroup();
            foreach ($havings as $i => $having) {
                if ($i > 0) {
                    $query->orOp();
                }
                $query->beginWhereGroup();
                $query->having($having["field"] . ">=", $having["min"], false, false);
                $query->having($having["field"] . "<=", $having["max"], false, false);
                $query->endWhereGroup();
            }
            $query->endWhereGroup();
        }
    }

    /**
     * Get a keyword with fields joined from the keywordSentiment table.
     *
     * @param int $keywordID
     * @return array|false Returns the keyword or false if not found.
     */
    public function getKeywordByID(int $keywordID)
    {
        $where = ["k.keywordID" => $keywordID];

        $keyword = $this->database
            ->sql()
            ->select(
                "k.keywordID, k.keyword, k.tracked, sum(ks.occurrences) as occurrences, avg(ks.sentimentScore) as averageSentiment, k.dateInserted, k.insertUserID, max(ks.dateInserted) as dateLastUsed"
            )
            ->from("keyword k")
            ->leftJoin("keywordSentiment ks", "k.keywordID = ks.keywordID")
            ->where($where)
            ->groupBy("k.keywordID")
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        return $keyword;
    }

    /**
     * Create the keywordSentiment table.
     */
    public static function structure(Gdn_Database $database): void
    {
        $database
            ->structure()
            ->table(self::KEYWORD_SENTIMENT_TABLE_NAME)
            ->column("keywordID", "int")
            ->column("sentimentScore", "int")
            ->column("recordID", "int")
            ->column("recordType", "varchar(255)")
            ->column("placeRecordID", "int")
            ->column("placeRecordType", "varchar(255)")
            ->column("occurrences", "int", 1)
            ->column("recordUserID", "int")
            ->column("dateInserted", "datetime")
            ->set();

        $database
            ->structure()
            ->table(self::KEYWORD_SENTIMENT_TABLE_NAME)
            ->createIndexIfNotExists("IX_keywordSentiment_keywordID_sentimentScore", ["keywordID", "sentimentScore"])
            ->createIndexIfNotExists("IX_keywordSentiment_dateInserted", ["dateInserted"]);
    }
}
