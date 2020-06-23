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
     * @throws \Exception
     */
    public function __construct(array $searchTypes, array $queryData, \Gdn_Database $db) {
        $this->db = $db->sql();
        parent::__construct($searchTypes, $queryData);
    }

    /**
     * @inheritDoc
     */
    public function whereText(string $text, array $fieldNames = []): self {
        return $this;
    }

    /**
     * @inheritDoc
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
            $sql .= ' ORDER BY DateInserted DESC '.PHP_EOL;
            $limit = $this->getQueryParameter('limit', 100);
            $offset = $this->getQueryParameter('offset', 0);
            $sql .= ' LIMIT '.$limit;
            $sql .= ($offset > 0) ? ', '.$offset : '';
        }
        return $sql;
    }

    /**
     * Get db driver
     *
     * @return \Gdn_SQLDriver
     */
    public function getDB() {
        return $this->db;
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
