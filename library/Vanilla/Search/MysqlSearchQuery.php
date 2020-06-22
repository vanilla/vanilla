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

    public function __construct(array $searchTypes, array $queryData, \Gdn_Database $db) {
        $this->db = $db->sql();
        parent::__construct($searchTypes, $queryData);
    }

    public function whereText(string $text, array $fieldNames = []): self {
        return $this;
    }

    public function setFilter(
        string $attribute,
        array $values,
        bool $exclude = false,
        string $filterOp = SearchQuery::FILTER_OP_OR
    ): self {
        return $this;
    }

    public function getSql() {
        $sql = '';
        if (count($this->sql) < 2) {
            $sql = reset($this->sql);
        } else {
            foreach ($this->sql as $subQuery) {
                $sql .= empty($sql) ? '' : ' union all ';
                $sql .= ' ( '.$subQuery.' ) ';
            }
        }
        $sql .= ' ORDER BY DateInserted DESC '.PHP_EOL;
        $limit = $this->getQueryParameter('limit', 100);
        $offset = $this->getQueryParameter('offset', 0);
        $sql .= ' LIMIT '.$limit;
        $sql .= ($offset > 0) ? ', '.$offset : '';

        return $sql;
    }

    public function getDB() {
        return $this->db;
    }

    public function get(string $param, $default = null) {
        return $this->getQueryParameter($param, $default);
    }

    public function addSql(string $sql) {
        $this->sql[] = $sql;
    }
}
