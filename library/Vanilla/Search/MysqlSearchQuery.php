<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;

/**
 * Mysql version of a search query.
 */
class MysqlSearchQuery extends SearchQuery {
    /** @var \Gdn_SQLDriver $db */
    private $db;

    /** @var array $sql */
    private $sql;

    /**
     * MysqlSearchQuery constructor.
     *
     * @param array $searchTypes
     * @param array $queryData
     * @param \Gdn_Database $db
     */
    public function __construct(array $searchTypes, array $queryData, \Gdn_Database $db) {
        $this->db = $db->sql();
        parent::__construct($searchTypes, $queryData);
    }

    /**
     * @inheritdoc
     */
    public function whereText(string $text, array $fieldNames = [], string $matchMode = self::MATCH_FULLTEXT, ?string $locale = ""): self {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setFilter(
        string $attribute,
        array $values,
        bool $exclude = false,
        string $filterOp = SearchQuery::FILTER_OP_OR
    ): self {
        return $this;
    }

    /**
     * Generate sql union query
     */
    public function getSql() {
        $sql = '';
        if (empty($this->sql)) {
            ;
        } elseif (count($this->sql) < 2) {
            $sql = reset($this->sql);
        } else {
            foreach ($this->sql as $subQuery) {
                $sql .= empty($sql) ? '' : ' union all ';
                $sql .= ' ( '.$subQuery.' ) ';
            }

            $sql .= $this->getOrderBy();
            $limit = $this->getQueryParameter('limit', 100);
            $offset = $this->getQueryParameter('offset', 0);
            $sql .= ' LIMIT '.$limit;
            $sql .= ($offset > 0) ? ', '.$offset : '';
        }
        return $sql;
    }

    /**
     * @return string
     */
    private function getOrderBy(): string {
        $sort = $this->getQueryParameter('sort', SearchQuery::SORT_RELEVANCE);
        $sortField = ltrim($sort, '-');
        $direction = $sortField === $sort ? 'DESC' : 'ASC';
        if ($sortField === SearchQuery::SORT_RELEVANCE) {
            $sortField = 'Score';
        }
        return "ORDER BY " . $this->getDB()->quote($sortField) . ' ' . $direction . PHP_EOL;
    }

    /**
     * Get db driver
     *
     * @return \Gdn_SQLDriver
     */
    public function getDB() {
        $sql = clone $this->db;
        $sql->reset();
        return $sql;
    }

    /**
     * Get query parameter
     *
     * @param string $param
     * @param null $default
     * @return mixed|null
     */
    public function get(string $param, $default = null) {
        return $this->getQueryParameter($param, $default);
    }

    /**
     * Add sql (one per particular SearchType)
     *
     * @param string $sql
     */
    public function addSql(string $sql) {
        if (!empty($sql)) {
            $this->sql[] = $sql;
        }
    }
}
