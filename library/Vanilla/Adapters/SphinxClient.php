<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @psalm-suppress all
 */

namespace Vanilla\Adapters;

/**
 * Class SphinxClient
 * @package Vanilla\Sphinx\Library
 */
class SphinxClient {
    // sphinx commands
    const SEARCH = 0;
    const EXCERPT = 1;
    const UPDATE = 2;
    const KEYWORDS = 3;
    const PERSIST = 4;
    const STATUS = 5;
    const FLUSHATTRS = 7;

    // command versions
    //const VER_SEARCH = 0x120;
    const VER_SEARCH = 0x11E;
    const VER_EXCERPT = 0x105;
    const VER_UPDATE = 0x104;
    const VER_KEYWORDS = 0x100;
    const VER_STATUS = 0x101;
    const VER_QUERY = 0x100;
    const VER_FLUSHATTRS = 0x100;

    const STATUS_OK = 0;
    const STATUS_ERROR = 1;
    const STATUS_RETRY = 2;
    const STATUS_WARNING = 3;

    const RANK_PROXIMITY_BM15 = 0;
    const RANK_BM15 = 1;
    const RANK_NONE = 2;
    const RANK_WORDCOUNT = 3;
    const RANK_PROXIMITY = 4;
    const RANK_MATCHANY = 5;
    const RANK_FIELDMASK = 6;
    const RANK_SPH04 = 7;
    const RANK_EXPR = 8;
    const RANK_TOTAL = 9;

    const SORT_RELEVANCE = 0;
    const SORT_ATTR_DESC = 1;
    const SORT_ATTR_ASC = 2;
    const SORT_EXTENDED = 4;

    const FILTER_VALUES = 0;
    const FILTER_RANGE = 1;
    const FILTER_FLOATRANGE = 2;
    const FILTER_STRING = 3;
    const FILTER_STRING_LIST = 6;

    const ATTR_INTEGER = 1;
    const ATTR_TIMESTAMP = 2;
    const ATTR_ORDINAL = 3;
    const ATTR_BOOL = 4;
    const ATTR_FLOAT = 5;
    const ATTR_BIGINT = 6;
    const ATTR_STRING = 7;
    const ATTR_FACTORS = 1001;
    const ATTR_MULTI = 0x40000001;
    const ATTR_MULTI64 = 0x40000002;

    const GROUPBY_DAY = 0;
    const GROUPBY_WEEK = 1;
    const GROUPBY_MONTH = 2;
    const GROUPBY_YEAR = 3;
    const GROUPBY_ATTR = 4;
    const GROUPBY_ATTRPAIR = 5;

    const UPDATE_PLAIN = 0;
    const UPDATE_MVA = 1;
    const UPDATE_STRING = 2;
    const UPDATE_JSON = 3;

    private $host = "localhost";
    private $port = 9312;
    private $path = false;
    private $socket = false;
    private $offset = 0;
    private $limit = 20;
    private $sort = self::SORT_RELEVANCE;
    private $sortBy = "";
    private $minID = 0;
    private $maxID = 0;
    private $filters = [];
    private $groupBy = '';
    private $groupFunc = self::GROUPBY_DAY;
    private $groupSort = "@group desc";
    private $groupDistinct = "";
    private $maxMatches = 1000000;
    private $cutOff = 0;
    private $retryCount = 0;
    private $retryDelay = 0;
    private $indexWeights = [];
    private $ranker = self::RANK_PROXIMITY_BM15;
    private $rankExpr = '';
    private $maxQueryTime = 0;
    private $fieldWeights = [];
    private $select = '*';
    private $queryFlags;
    private $predictedTime = 0;
    private $outerOrderBy = "";
    private $outerOffset = 0;
    private $outerLimit = 0;
    private $hasOuter = false;
    private $tokenFilterLibrary = '';
    private $tokenFilterName = '';
    private $tokenFilterOpts = '';

    private $error = '';
    private $warning = '';
    private $connError = false;

    private $reqs = [];
    private $mbenc = '';
    private $arrayResult = false;
    private $timeout = 0;

    /**
     * SphinxClient constructor.
     */
    public function __construct() {
        $this->queryFlags = $this->setBit(0, 6, true);
    }

    /**
     * SphinxClient destructor.
     */
    public function __destruct() {
        if ($this->socket !== false) {
            fclose($this->socket);
        }
    }

    /**
     * Set sphinx host and port
     *
     * @param string $host
     * @param int $port
     */
    public function setServer(string $host, int $port = 9312) {
        if ($host[0] === '/') {
            $this->path = 'unix://' . $host;
            return;
        }
        if (substr($host, 0, 7) === "unix://") {
            $this->path = $host;
            return;
        }

        $this->host = $host;
        $this->port = $port;
        $this->path = '';
    }

    /**
     * Connect to sphinx server and return resource reference
     *
     * @return bool|false|resource
     */
    private function connect() {
        if ($this->socket !== false) {
            if (!@feof($this->socket)) {
                return $this->socket;
            }
            $this->socket = false;
        }

        $errno = 0;
        $errstr = "";
        $this->connError = false;

        if ($this->path) {
            $host = $this->path;
            $port = 0;
        } else {
            $host = $this->host;
            $port = $this->port;
        }

        if ($this->timeout <= 0) {
            $conn = @fsockopen($host, $port, $errno, $errstr);
        } else {
            $conn = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        }

        if (!$conn) {
            if ($this->path) {
                $location = $this->path;
            } else {
                $location = "{$this->host}:{$this->port}";
            }

            $errstr = trim($errstr);
            $this->error = "Connection to $location failed (errno=$errno, msg=$errstr)";
            $this->connError = true;
            return false;
        }

        if (!$this->send($conn, pack("N", 1), 4)) {
            fclose($conn);
            $this->error = "failed to send client protocol version";
            return false;
        }

        [, $version] = unpack("N*", fread($conn, 4));
        return $conn;
    }

    /**
     * Send data to server
     *
     * @param resource $conn
     * @param string $data
     * @param int|null $length
     * @return bool
     */
    private function send($conn, string $data, ?int $length): bool {
        if (feof($conn) || fwrite($conn, $data, $length) !== $length) {
            $this->error = 'Connection closed';
            $this->connError = true;
            return false;
        }
        return true;
    }


    /**
     * Close sphinx connection
     *
     * @return bool
     */
    private function close(): bool {
        if ($this->socket === false) {
            $this->error = 'not connected';
            return false;
        }

        fclose($this->socket);
        $this->socket = false;

        return true;
    }

    /**
     * Get response from sphinx server
     *
     * @param resource $conn
     * @return string
     */
    private function getResponse($conn): string {
        $response = "";
        $len = 0;

        $header = fread($conn, 8);
        if (strlen($header) === 8) {
            [$status, $ver, $len] = array_values(unpack("n2a/Nb", $header));
            $left = $len;
            while ($left > 0 && !feof($conn)) {
                $chunk = fread($conn, min(8192, $left));
                if ($chunk) {
                    $response .= $chunk;
                    $left -= strlen($chunk);
                }
            }
        }
        if ($this->socket === false) {
            fclose($conn);
        }
        // check response
        $read = strlen($response);
        if (!$response || $read !== $len) {
            $this->error = $len
                ? "Failed to read searchd response (status=$status, ver=$ver, len=$len, read=$read)"
                : "Received zero-sized searchd response";
            return false;
        }

        // check status
        if ($status == self::STATUS_WARNING) {
            [, $wlen] = unpack("N*", substr($response, 0, 4));
            $this->warning = substr($response, 4, $wlen);
            return substr($response, 4 + $wlen);
        }
        if ($status === self::STATUS_ERROR) {
            $this->error = "Sphinx error: " . substr($response, 4);
            return false;
        }
        if ($status === self::STATUS_RETRY) {
            $this->error = "Retry sphinx error: " . substr($response, 4);
            return false;
        }
        if ($status != self::STATUS_OK) {
            $this->error = "Unknown status code '$status'";
            return false;
        }

        return $response;
    }

    /**
     * Status
     *
     * @param bool $session
     * @return array|bool
     */
    public function status(bool $session = false) {
        $this->mbPush();
        if (!($conn = $this->connect())) {
            $this->mbPop();
            return false;
        }

        $req = pack("nnNN", self::STATUS, self::VER_STATUS, 4, $session ? 0 : 1); // len=4, body=1
        if (!($this->send($conn, $req, 12)) ||
            !($response = $this->getResponse($conn, self::VER_STATUS))) {
            $this->mbPop();
            return false;
        }

        $p = 0;
        [$rows, $cols] = array_values(unpack("N*N*", substr($response, $p, 8)));
        $p += 8;

        $res = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                [, $len] = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $res[$i][] = substr($response, $p, $len);
                $p += $len;
            }
        }

        $this->mbPop();
        return $res;
    }

    /**
     * Flush attributes
     *
     * @return int
     */
    public function flushAttributes(): int {
        $this->mbPush();
        if (!($conn = $this->connect())) {
            $this->mbPop();
            return -1;
        }

        $req = pack("nnN", self::FLUSHATTRS, self::VER_FLUSHATTRS, 0);
        if (!($this->send($conn, $req, 8)) ||
            !($response = $this->getResponse($conn, self::VER_FLUSHATTRS))) {
            $this->mbPop();
            return -1;
        }

        $tag = -1;
        if (strlen($response) == 4) {
            [, $tag] = unpack("N*", $response);
        } else {
            $this->error = "Unexpected response length";
        }

        $this->mbPop();
        return $tag;
    }

    /**
     * Set matches sorting mode
     *
     * @param int $mode
     * @param string $sortBy
     */
    public function setSortMode(int $mode, string $sortBy = "") {
        assert(
            $mode === self::SORT_RELEVANCE ||
            $mode === self::SORT_ATTR_DESC ||
            $mode === self::SORT_ATTR_ASC ||
            $mode === self::SORT_EXTENDED
        );
        assert($mode === self::SORT_RELEVANCE || strlen($sortBy) > 0);

        $this->sort = $mode;
        $this->sortBy = $sortBy;
    }

    /**
     * Set filter values for numeric attribute
     *
     * @param string $attribute
     * @param array $values Values should be numeric
     * @param bool $exclude
     */
    public function setFilter(string $attribute, array $values, bool $exclude = false) {
        if (count($values)) {
            $this->filters[] = [
                "type" => self::FILTER_VALUES,
                "attr" => $attribute,
                "exclude" => $exclude,
                "values" => $values
            ];
        }
    }

    /**
     * Prepare string values for sphinx query builder
     *
     * @param string $str
     * @return string
     */
    public static function escapeString(string $str): string {
        $from = ['\\', '(', ')', '|', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=', '<'];
        $to = ['\\\\', '\(', '\)', '\|', '\-', '\!', '\@', '\~', '\"', '\&', '\/', '\^', '\$', '\=', '\<'];

        return str_replace($from, $to, $str);
    }

    /**
     * Clear filters
     */
    public function resetFilters() {
        $this->filters = [];
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function getLastError(): string {
        return $this->error;
    }

    /**
     * Get last warning message.
     *
     * @return string
     */
    public function getLastWarning(): string {
        return $this->warning;
    }

    /**
     * Check last error type.
     *
     * @return bool
     */
    public function isConnectError(): bool {
        return $this->connError;
    }

    /**
     * Set connection timeout.
     * Note: when set to 0 there is no limit
     *
     * @param int $timeout
     */
    public function setConnectTimeout(int $timeout) {
        $this->timeout = $timeout;
    }

    /**
     * Set limit and offset to get from result set
     *
     * @param int $offset
     * @param int $limit
     * @param int $max
     * @param int $cutoff
     */
    public function setLimits(int $offset, int $limit, int $max = 0, int $cutoff = 0) {
        $this->offset = $offset;
        $this->limit = $limit;
        if ($max > 0) {
            $this->maxMatches = $max;
        }
        if ($cutoff > 0) {
            $this->cutOff = $cutoff;
        }
    }

    /**
     * Set timeout for query to execute
     * Note: 0 means no limit
     *
     * @param int $max
     */
    public function setMaxQueryTime(int $max) {
        $this->maxQueryTime = $max;
    }

    /**
     * Set ranking mode.
     * Options: RANK_PROXIMITY_BM15, RANK_BM15, RANK_NONE, RANK_WORDCOUNT, RANK_PROXIMITY
     *          RANK_MATCHANY, RANK_FIELDMASK, RANK_SPH04, RANK_EXPR, RANK_TOTAL
     *
     * @param int $ranker
     * @param string $rankExpr
     */
    public function setRankingMode(int $ranker, string $rankExpr = "") {
        assert($ranker === 0 || $ranker >= 1 && $ranker < self::RANK_TOTAL);
        $this->ranker = $ranker;
        $this->rankexpr = $rankExpr;
    }

    /**
     * Set field weights.
     * @param array $weights Associative array of key value pairs: (string)fieldName => (int)weight
     */
    public function setFieldWeights(array $weights) {
        $this->fieldWeights = $weights;
    }

    /**
     * Set index weights.
     * @param array $weights Associative array of key value pairs: (string)indexName => (int)weight
     */
    public function setIndexWeights(array $weights) {
        $this->indexWeights = $weights;
    }

    /**
     * Set document ID range to filter
     *
     * @param int $min
     * @param int $max
     */
    public function setIDRange(int $min, int $max) {
        $this->minID = $min;
        $this->maxID = $max;
    }

    /**
     * Set string attribute to filter
     *
     * @param string $attribute
     * @param string $value
     * @param bool $exclude
     */
    public function setFilterString(string $attribute, string $value, bool $exclude = false) {
        $this->filters[] = [
            "type" => self::FILTER_STRING,
            "attr" => $attribute,
            "exclude" => $exclude,
            "value" => $value
        ];
    }

    /**
     * Set string filter
     *
     * @param string $attribute
     * @param array $value Array of string values to filter by
     * @param bool $exclude
     */
    public function setFilterStringList(string $attribute, array $value, bool $exclude = false) {
        $this->filters[] = [
            "type" => self::FILTER_STRING_LIST,
            "attr" => $attribute,
            "exclude" => $exclude,
            "values" => $value
        ];
    }

    /**
     * Set int range filter
     *
     * @param string $attribute
     * @param int $min
     * @param int $max
     * @param bool $exclude
     */
    public function setFilterRange(string $attribute, int $min, int $max, bool $exclude = false) {
        $this->filters[] = [
            "type" => self::FILTER_RANGE,
            "attr" => $attribute,
            "exclude" => $exclude,
            "min" => $min,
            "max" => $max
        ];
    }

    /**
     * Set float range filter
     *
     * @param string $attribute
     * @param float $min
     * @param float $max
     * @param bool $exclude
     */
    public function setFilterFloatRange(string $attribute, float $min, float $max, bool $exclude = false) {
        $this->filters[] = [
            "type" => self::FILTER_FLOATRANGE,
            "attr" => $attribute,
            "exclude" => $exclude,
            "min" => $min,
            "max" => $max
        ];
    }

    /**
     * Set groupBy and groupFunc attributes
     *
     * @param string $attribute
     * @param int $func
     * @param string $groupSort
     */
    public function setGroupBy(string $attribute, int $func, string $groupSort = "@group desc") {
        assert($func === self::GROUPBY_DAY
            || $func === self::GROUPBY_WEEK
            || $func === self::GROUPBY_MONTH
            || $func === self::GROUPBY_YEAR
            || $func === self::GROUPBY_ATTR
            || $func === self::GROUPBY_ATTRPAIR);

        $this->groupBy = $attribute;
        $this->groupFunc = $func;
        $this->groupSort = $groupSort;
    }

    /**
     * Set count distinct attribute.
     * Note: applies only together with setGroupBy() method
     *
     * @param string $attribute
     */
    public function setGroupDistinct(string $attribute) {
        $this->groupDistinct = $attribute;
    }

    /**
     * Set retries count and delay
     *
     * @param int $count
     * @param int $delay
     */
    public function setRetries(int $count, int $delay = 0) {
        $this->retryCount = $count;
        $this->retryDelay = $delay;
    }

    /**
     * Set sphinx select query
     *
     * @param string $select
     */
    public function setSelect(string $select) {
        $this->select = $select;
    }

    /**
     * Set some specific query flag
     *
     * @param string $flagName
     * @param mixed $flagValue
     */
    public function setQueryFlag(string $flagName, $flagValue) {
        $flags = [
            "reverse_scan" => [0, 1],
            "sort_method" => ["pq", "kbuffer"],
            "max_predicted_time" => [0],
            "boolean_simplify" => [true, false],
            "idf" => ["normalized", "plain", "tfidf_normalized", "tfidf_unnormalized"],
            "global_idf" => [true, false],
            "low_priority" => [true, false]
        ];

        assert(
            in_array(
                $flagValue,
                $flags[$flagName] ?? [],
                true
            )
            || ($flagName === "max_predicted_time" && is_int($flagValue) && $flagValue >= 0)
        );

        if ($flagName == "reverse_scan") {
            $this->queryFlags =$this->setBit($this->queryFlags, 0, $flagValue === 1);
        }
        if ($flagName == "sort_method") {
            $this->queryFlags =$this->setBit($this->queryFlags, 1, $flagValue === "kbuffer");
        }
        if ($flagName == "max_predicted_time") {
            $this->queryFlags =$this->setBit($this->queryFlags, 2, $flagValue > 0);
            $this->predictedtime = (int)$flagValue;
        }
        if ($flagName == "boolean_simplify") {
            $this->queryFlags =$this->setBit($this->queryFlags, 3, $flagValue);
        }
        if ($flagName == "idf" && ($flagValue == "normalized" || $flagValue === "plain")) {
            $this->queryFlags =$this->setBit($this->queryFlags, 4, $flagValue === "plain");
        }
        if ($flagName == "global_idf") {
            $this->queryFlags =$this->setBit($this->queryFlags, 5, $flagValue);
        }
        if ($flagName == "idf" && ($flagValue == "tfidf_normalized" || $flagValue === "tfidf_unnormalized")) {
            $this->queryFlags =$this->setBit($this->queryFlags, 6, $flagValue === "tfidf_normalized");
        }
        if ($flagName == "low_priority") {
            $this->queryFlags =$this->setBit($this->queryFlags, 8, $flagValue);
        }
    }

    /**
     * Set outer order by parameters
     *
     * @param string $orderBy
     * @param int $offset
     * @param int $limit
     */
    public function setOuterSelect(string $orderBy, int $offset, int $limit) {
        assert($limit > 0);

        $this->outerOrderBy = $orderBy;
        $this->outerOffset = $offset;
        $this->outerLimit = $limit;
        $this->hasOuter = true;
    }

    /**
     * Set token attributes.
     *
     * @param string $library
     * @param string $name
     * @param string $opts
     */
    public function setTokenFilter(string $library, string $name, string $opts = "") {
        $this->tokenFilterLibrary = $library;
        $this->tokenFilterName = $name;
        $this->tokenFilterOpts = $opts;
    }

    /**
     * Reset grouping attributes
     */
    public function resetGroupBy() {
        $this->groupBy = "";
        $this->groupFunc = self::GROUPBY_DAY;
        $this->groupSort = "@group desc";
        $this->groupDistinct = "";
    }

    /**
     * Reset query flags
     */
    public function resetQueryFlag() {
        $this->queryFlags =$this->setBit(0, 6, true); // default idf=tfidf_normalized
        $this->predictedTime = 0;
    }

    /**
     * Reset outer query attributes
     */
    public function resetOuterSelect() {
        $this->outerOrderBy = '';
        $this->outerOffset = 0;
        $this->outerLimit = 0;
        $this->hasOuter = false;
    }

    /**
     * Run single query and return search results
     *
     * @param string $query
     * @param string $index
     * @param string $comment
     * @return bool|mixed
     */
    public function query(string $query, string $index = "*", string $comment = "") {
        assert(empty($this->reqs));

        $this->addQuery($query, $index, $comment);
        $results = $this->runQueries();
        $this->reqs = [];

        if (!is_array($results)) {
            return false;
        }

        $res = reset($results);
        $this->error = $res["error"];
        $this->warning = $res["warning"];
        if ($res["status"] == self::STATUS_ERROR) {
            return false;
        } else {
            return $res;
        }
    }

    /**
     * Add query
     *
     * @param string $query
     * @param string $index
     * @param string $comment
     * @return int|void
     */
    public function addQuery(string $query, string $index = "*", string $comment = "") {
        $this->mbPush();

        $req = pack("NNNNN", $this->queryFlags, $this->offset, $this->limit, 6, $this->ranker);
        if ($this->ranker === self::RANK_EXPR) {
            $req .= pack("N", strlen($this->rankExpr)) . $this->rankExpr;
        }
        $req .= pack("N", $this->sort); // (deprecated) sort mode
        $req .= pack("N", strlen($this->sortBy)) . $this->sortBy;
        $req .= pack("N", strlen($query)) . $query; // query itself
        $req .= pack("N", 0); // weights
        $req .= pack("N", strlen($index)) . $index; // indexes
        $req .= pack("N", 1); // range marker
        $req .= $this->packU64($this->minID) . $this->packU64($this->maxID);

        $req .= pack("N", count($this->filters));
        foreach ($this->filters as $filter) {
            $req .= pack("N", strlen($filter["attr"])) . $filter["attr"];
            $req .= pack("N", $filter["type"]);
            switch ($filter["type"]) {
                case self::FILTER_VALUES:
                    $req .= pack("N", count($filter["values"]));
                    foreach ($filter["values"] as $value) {
                        $req .= $this->packI64($value);
                    }
                    break;

                case self::FILTER_RANGE:
                    $req .= $this->packI64($filter["min"]) . $this->packI64($filter["max"]);
                    break;

                case self::FILTER_FLOATRANGE:
                    $req .= $this->packFloat($filter["min"]) . $this->packFloat($filter["max"]);
                    break;

                case self::FILTER_STRING:
                    $req .= pack("N", strlen($filter["value"])) . $filter["value"];
                    break;

                case self::FILTER_STRING_LIST:
                    $req .= pack("N", count($filter["values"]));
                    foreach ($filter["values"] as $value) {
                        $req .= pack("N", strlen($value)) . $value;
                    }
                    break;

                default:
                    assert(0 && "internal error: unhandled filter type");
            }
            $req .= pack("N", $filter["exclude"]);
        }

        // group-by clause, max-matches count, group-sort clause, cutoff count
        $req .= pack("NN", $this->groupFunc, strlen($this->groupBy)) . $this->groupBy;
        $req .= pack("N", $this->maxMatches);
        $req .= pack("N", strlen($this->groupSort)) . $this->groupSort;
        $req .= pack("NNN", $this->cutOff, $this->retryCount, $this->retryDelay);
        $req .= pack("N", strlen($this->groupDistinct)) . $this->groupDistinct;

        // geoanchor point
        $req .= pack("N", 0);

        // index weights
        $req .= pack("N", count($this->indexWeights));
        foreach ($this->indexWeights as $idx => $weight) {
            $req .= pack("N", strlen($idx)) . $idx . pack("N", $weight);
        }

        $req .= pack("N", $this->maxQueryTime);

        // field weights
        $req .= pack("N", count($this->fieldWeights));
        foreach ($this->fieldWeights as $field => $weight) {
            $req .= pack("N", strlen($field)) . $field . pack("N", $weight);
        }

        $req .= pack("N", strlen($comment)) . $comment;

        // attribute overrides
        $req .= pack("N", 0);

        $req .= pack("N", strlen($this->select)) . $this->select;

        if ($this->predictedTime > 0) {
            $req .= pack("N", (int)$this->predictedTime);
        }

        $req .= pack("N", strlen($this->outerOrderBy)) . $this->outerOrderBy;
        $req .= pack("NN", $this->outerOffset, $this->outerLimit);
        if ($this->hasOuter) {
            $req .= pack("N", 1);
        } else {
            $req .= pack("N", 0);
        }

        // token_filter
        $req .= pack("N", strlen($this->tokenFilterLibrary)) . $this->tokenFilterLibrary;
        $req .= pack("N", strlen($this->tokenFilterName)) . $this->tokenFilterName;
        $req .= pack("N", strlen($this->tokenFilterOpts)) . $this->tokenFilterOpts;

        $this->mbPop();

        $this->reqs[] = $req;
        return count($this->reqs) - 1;
    }

    /**
     * Run all queries prepared and return search results
     *
     * @return array|bool
     */
    public function runQueries() {
        if (empty($this->reqs)) {
            $this->error = "no queries defined, issue AddQuery() first";
            return false;
        }

        $this->mbPush();

        if (!($conn = $this->connect())) {
            $this->mbPop();
            return false;
        }

        $nreqs = count($this->reqs);
        $req = join("", $this->reqs);
        $len = 8 + strlen($req);
        $req = pack("nnNNN", self::SEARCH, self::VER_SEARCH, $len, 0, $nreqs) . $req; // add header

        if (!($this->send($conn, $req, $len + 8)) ||
            !($response = $this->getResponse($conn, self::VER_SEARCH))) {
            $this->mbPop();
            return false;
        }

        $this->reqs = [];

        return $this->parseSearchResponse($response, $nreqs);
    }

    ///

    /**
     * Parse and return sphinx response
     *
     * @param string $response
     * @param int $nreqs
     * @return array
     */
    private function parseSearchResponse(string $response, int $nreqs) {
        $p = 0; // current position
        $max = strlen($response); // max position for checks, to protect against broken responses

        $results = [];
        for ($ires = 0; $ires < $nreqs && $p < $max; $ires++) {
            $results[] = [];
            $result =& $results[$ires];

            $result["error"] = "";
            $result["warning"] = "";

            [, $status] = unpack("N*", substr($response, $p, 4));
            $p += 4;
            $result["status"] = $status;
            if ($status !== self::STATUS_OK) {
                [, $len] = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $message = substr($response, $p, $len);
                $p += $len;

                if ($status == self::STATUS_WARNING) {
                    $result["warning"] = $message;
                } else {
                    $result["error"] = $message;
                    continue;
                }
            }

            // read schema
            $fields = [];
            $attrs = [];

            [, $nfields] = unpack("N*", substr($response, $p, 4));
            $p += 4;
            while ($nfields-- > 0 && $p < $max) {
                [, $len] = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $fields[] = substr($response, $p, $len);
                $p += $len;
            }
            $result["fields"] = $fields;

            [, $nattrs] = unpack("N*", substr($response, $p, 4));
            $p += 4;
            while ($nattrs-- > 0 && $p < $max) {
                [, $len] = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $attr = substr($response, $p, $len);
                $p += $len;
                [, $type] = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $attrs[$attr] = $type;
            }
            $result["attrs"] = $attrs;

            // read match count
            [, $count] = unpack("N*", substr($response, $p, 4));
            $p += 4;
            [, $id64] = unpack("N*", substr($response, $p, 4));
            $p += 4;

            // read matches
            $idx = -1;
            while ($count-- > 0 && $p < $max) {
                // index into result array
                $idx++;

                // parse document id and weight
                if ($id64) {
                    $doc = $this->unPackU64(substr($response, $p, 8));
                    $p += 8;
                    [, $weight] = unpack("N*", substr($response, $p, 4));
                    $p += 4;
                } else {
                    [$doc, $weight] = array_values(unpack(
                        "N*N*",
                        substr($response, $p, 8)
                    ));
                    $p += 8;
                    $doc = $this->fixUint($doc);
                }
                $weight = sprintf("%u", $weight);

                if ($this->arrayResult) {
                    $result["matches"][$idx] = ["id" => $doc, "weight" => $weight];
                } else {
                    $result["matches"][$doc]["weight"] = $weight;
                }

                $attrVals = [];
                foreach ($attrs as $attr => $type) {
                    if ($type === self::ATTR_BIGINT) {
                        $attrVals[$attr] = $this->unPackI64(substr($response, $p, 8));
                        $p += 8;
                        continue;
                    }

                    if ($type === self::ATTR_FLOAT) {
                        [, $uval] = unpack("N*", substr($response, $p, 4));
                        $p += 4;
                        [, $fval] = unpack("f*", pack("L", $uval));
                        $attrVals[$attr] = $fval;
                        continue;
                    }

                    // handle everything else as unsigned ints
                    [, $val] = unpack("N*", substr($response, $p, 4));
                    $p += 4;
                    if ($type === self::ATTR_MULTI) {
                        $attrVals[$attr] = [];
                        $nvalues = $val;
                        while ($nvalues-- > 0 && $p < $max) {
                            [, $val] = unpack("N*", substr($response, $p, 4));
                            $p += 4;
                            $attrVals[$attr][] = $this->fixUint($val);
                        }
                    } else {
                        if ($type === self::ATTR_MULTI64) {
                            $attrVals[$attr] = [];
                            $nvalues = $val;
                            while ($nvalues > 0 && $p < $max) {
                                $attrVals[$attr][] = $this->unPackI64(substr($response, $p, 8));
                                $p += 8;
                                $nvalues -= 2;
                            }
                        } else {
                            if ($type === self::ATTR_STRING) {
                                $attrVals[$attr] = substr($response, $p, $val);
                                $p += $val;
                            } else {
                                if ($type === self::ATTR_FACTORS) {
                                    $attrVals[$attr] = substr($response, $p, $val - 4);
                                    $p += $val - 4;
                                } else {
                                    $attrVals[$attr] = $this->fixUint($val);
                                }
                            }
                        }
                    }
                }

                if ($this->arrayResult) {
                    $result["matches"][$idx]["attrs"] = $attrVals;
                } else {
                    $result["matches"][$doc]["attrs"] = $attrVals;
                }
            }

            [$total, $totalFound, $msecs, $words] =
                array_values(unpack("N*N*N*N*", substr($response, $p, 16)));
            $result["total"] = sprintf("%u", $total);
            $result["total_found"] = sprintf("%u", $totalFound);
            $result["time"] = sprintf("%.3f", $msecs / 1000);
            $p += 16;

            while ($words-- > 0 && $p < $max) {
                [, $len] = unpack("N*", substr($response, $p, 4));
                $p += 4;
                $word = substr($response, $p, $len);
                $p += $len;
                [$docs, $hits] = array_values(unpack("N*N*", substr($response, $p, 8)));
                $p += 8;
                $result["words"][$word] = [
                    "docs" => sprintf("%u", $docs),
                    "hits" => sprintf("%u", $hits)
                ];
            }
        }

        $this->mbPop();
        return $results;
    }

    /**
     * Update sphinx attributes
     *
     * @param string $index
     * @param array $attrs
     * @param array $values
     * @param int $type
     * @param bool $ignorenonexistent
     * @return int
     */
    public function updateAttributes(
        string $index,
        array $attrs,
        array $values,
        $type = self::UPDATE_PLAIN,
        bool $ignorenonexistent = false
    ): int {
        assert(
            $type === self::UPDATE_PLAIN
            || $type === self::UPDATE_MVA
            || $type === self::UPDATE_STRING
            || $type === self::UPDATE_JSON
        );

        $mva = $type == self::UPDATE_MVA;
        $string = $type === self::UPDATE_STRING || $type == self::UPDATE_JSON;

        foreach ($attrs as $attr) {
            assert(is_string($attr));
        }

        foreach ($values as $id => $entry) {
            assert(is_numeric($id));
            assert(is_array($entry));
            assert(count($entry) === count($attrs));
            foreach ($entry as $v) {
                if ($mva) {
                    assert(is_array($v));
                    foreach ($v as $vv) {
                        assert(is_int($vv));
                    }
                } else {
                    if ($string) {
                        assert(is_string($v));
                    } else {
                        assert(is_int($v));
                    }
                }
            }
        }

        // build request
        $this->mbPush();
        $req = pack("N", strlen($index)) . $index;

        $req .= pack("N", count($attrs));
        $req .= pack("N", $ignorenonexistent ? 1 : 0);
        foreach ($attrs as $attr) {
            $req .= pack("N", strlen($attr)) . $attr;
            $req .= pack("N", $type);
        }

        $req .= pack("N", count($values));
        foreach ($values as $id => $entry) {
            $req .= $this->packU64($id);
            foreach ($entry as $v) {
                $nvalues = $mva ? count($v) : ($string ? strlen($v) : $v);
                $req .= pack("N", $nvalues);
                if ($mva) {
                    foreach ($v as $vv) {
                        $req .= pack("N", $vv);
                    }
                } else {
                    if ($string) {
                        $req .= $v;
                    }
                }
            }
        }

        // connect, send query, get response
        if (!($conn = $this->connect())) {
            $this->mbPop();
            return -1;
        }

        $len = strlen($req);
        $req = pack("nnN", self::UPDATE, self::VER_UPDATE, $len) . $req; // add header
        if (!$this->send($conn, $req, $len + 8)) {
            $this->mbPop();
            return -1;
        }

        if (!($response = $this->getResponse($conn, self::VER_UPDATE))) {
            $this->mbPop();
            return -1;
        }

        // parse response
        [, $updated] = unpack("N*", substr($response, 0, 4));
        $this->mbPop();
        return $updated;
    }

    /**
     * Pack signed 64bit
     *
     * @param mixed $v
     * @return false|string
     */
    private function packI64($v) {
        assert(is_numeric($v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            $v = (int)$v;
            return pack("NN", $v >> 32, $v & 0xFFFFFFFF);
        }

        // x32, int
        if (is_int($v)) {
            return pack("NN", $v < 0 ? -1 : 0, $v);
        }

        // x32, bcmath
        if (function_exists("bcmul")) {
            if (bccomp($v, 0) == -1) {
                $v = bcadd("18446744073709551616", $v);
            } else {
                if (bccomp($v, "9223372036854775807") > 0) {
                    $v = "9223372036854775807";
                }
            } // clamp at 2^63-1 like a boss (ie. like 64-bit php would)
            $h = bcdiv($v, "4294967296", 0);
            $l = bcmod($v, "4294967296");
            return pack("NN", (float)$h, (float)$l); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = abs((float)substr($v, $p));
        $hi = abs((float)substr($v, 0, $p));

        $m = $lo + $hi * 1316134912.0; // (10 ^ 13) % (1 << 32) = 1316134912
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi * 2328.0 + $q; // (10 ^ 13) / (1 << 32) = 2328

        if ($v < 0) {
            if ($l == 0) {
                $h = 4294967296.0 - $h;
            } else {
                $h = 4294967295.0 - $h;
                $l = 4294967296.0 - $l;
            }
        }
        return pack("NN", $h, $l);
    }

    /**
     * Pack unsigned 64bit
     *
     * @param mixed $v
     * @return false|string
     */
    private function packU64($v) {
        assert(is_numeric($v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            assert($v >= 0);

            // x64, int
            if (is_int($v)) {
                return pack("NN", $v >> 32, $v & 0xFFFFFFFF);
            }

            // x64, bcmath
            if (function_exists("bcmul")) {
                $h = bcdiv($v, 4294967296, 0);
                $l = bcmod($v, 4294967296);
                return pack("NN", $h, $l);
            }

            // x64, no-bcmath
            $p = max(0, strlen($v) - 13);
            $lo = (int)substr($v, $p);
            $hi = (int)substr($v, 0, $p);

            $m = $lo + $hi * 1316134912;
            $l = $m % 4294967296;
            $h = $hi * 2328 + (int)($m / 4294967296);

            return pack("NN", $h, $l);
        }

        // x32, int
        if (is_int($v)) {
            return pack("NN", 0, $v);
        }

        // x32, bcmath
        if (function_exists("bcmul")) {
            $h = bcdiv($v, "4294967296", 0);
            $l = bcmod($v, "4294967296");
            return pack("NN", (float)$h, (float)$l); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = (float)substr($v, $p);
        $hi = (float)substr($v, 0, $p);

        $m = $lo + $hi * 1316134912.0;
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi * 2328.0 + $q;

        return pack("NN", $h, $l);
    }

    /**
     * Unpack unsigned 64bit
     *
     * @param mixed $v
     * @return float|int|string
     */
    private function unPackU64($v) {
        [$hi, $lo] = array_values(unpack("N*N*", $v));

        if (PHP_INT_SIZE >= 8) {
            // x64, int
            if ($hi <= 2147483647) {
                return ($hi << 32) + $lo;
            }

            // x64, bcmath
            if (function_exists("bcmul")) {
                return bcadd($lo, bcmul($hi, "4294967296"));
            }

            // x64, no-bcmath
            $C = 100000;
            $h = ((int)($hi / $C) << 32) + (int)($lo / $C);
            $l = (($hi % $C) << 32) + ($lo % $C);
            if ($l > $C) {
                $h += (int)($l / $C);
                $l = $l % $C;
            }

            if ($h == 0) {
                return $l;
            }
            return sprintf("%d%05d", $h, $l);
        }

        // x32, int
        if ($hi == 0) {
            if ($lo > 0) {
                return $lo;
            }
            return sprintf("%u", $lo);
        }

        $hi = sprintf("%u", $hi);
        $lo = sprintf("%u", $lo);

        // x32, bcmath
        if (function_exists("bcmul")) {
            return bcadd($lo, bcmul($hi, "4294967296"));
        }

        // x32, no-bcmath
        $hi = (float)$hi;
        $lo = (float)$lo;

        $q = floor($hi / 10000000.0);
        $r = $hi - $q * 10000000.0;
        $m = $lo + $r * 4967296.0;
        $mq = floor($m / 10000000.0);
        $l = $m - $mq * 10000000.0;
        $h = $q * 4294967296.0 + $r * 429.0 + $mq;

        $h = sprintf("%.0f", $h);
        $l = sprintf("%07.0f", $l);
        if ($h == "0") {
            return sprintf("%.0f", (float)$l);
        }
        return $h . $l;
    }

    /**
     * Unpack signed 64bit
     *
     * @param mixed $v
     * @return bool|float|int|string
     */
    private function unPackI64($v) {
        [$hi, $lo] = array_values(unpack("N*N*", $v));

        // x64
        if (PHP_INT_SIZE >= 8) {
            if ($hi < 0) {
                $hi += (1 << 32);
            }
            if ($lo < 0) {
                $lo += (1 << 32);
            }

            return ($hi << 32) + $lo;
        }

        // x32, int
        if ($hi == 0) {
            if ($lo > 0) {
                return $lo;
            }
            return sprintf("%u", $lo);
        } elseif ($hi == -1) {
            if ($lo < 0) {
                return $lo;
            }
            return sprintf("%.0f", $lo - 4294967296.0);
        }

        $neg = "";
        $c = 0;
        if ($hi < 0) {
            $hi = ~$hi;
            $lo = ~$lo;
            $c = 1;
            $neg = "-";
        }

        $hi = sprintf("%u", $hi);
        $lo = sprintf("%u", $lo);

        // x32, bcmath
        if (function_exists("bcmul")) {
            return $neg . bcadd(bcadd($lo, bcmul($hi, "4294967296")), $c);
        }

        // x32, no-bcmath
        $hi = (float)$hi;
        $lo = (float)$lo;

        $q = floor($hi / 10000000.0);
        $r = $hi - $q * 10000000.0;
        $m = $lo + $r * 4967296.0;
        $mq = floor($m / 10000000.0);
        $l = $m - $mq * 10000000.0 + $c;
        $h = $q * 4294967296.0 + $r * 429.0 + $mq;
        if ($l == 10000000) {
            $l = 0;
            $h += 1;
        }

        $h = sprintf("%.0f", $h);
        $l = sprintf("%07.0f", $l);
        if ($h == "0") {
            return $neg . sprintf("%.0f", (float)$l);
        }
        return $neg . $h . $l;
    }

    /**
     * Fix unsigned int.
     *
     * @param mixed $value
     * @return int|string
     */
    private function fixUint($value) {
        if (PHP_INT_SIZE >= 8) {
            if ($value < 0) {
                $value += (1 << 32);
            }
            return $value;
        } else {
            // x32 route, workaround php signed/unsigned
            return sprintf("%u", $value);
        }
    }

    /**
     * Set bit
     *
     * @param mixed $flag
     * @param int $bit
     * @param bool $on
     * @return int
     */
    private function setBit($flag, int $bit, bool $on) {
        if ($on) {
            $flag |= (1 << $bit);
        } else {
            $reset = 16777215 ^ (1 << $bit);
            $flag = $flag & $reset;
        }
        return $flag;
    }

    /**
     * Keep current encoding page if set.
     * And set it to latin1 if possible.
     */
    private function mbPush() {
        $this->mbenc = "";
        if (ini_get("mbstring.func_overload") & 2) {
            $this->mbenc = mb_internal_encoding();
            mb_internal_encoding("latin1");
        }
    }

    /**
     * Reset encoding page to the original one (saved by mbPush() method).
     */
    private function mbPop() {
        if ($this->mbenc) {
            mb_internal_encoding($this->mbenc);
        }
    }

    /**
     * Pack floats in network byte order
     *
     * @param mixed $f
     * @return string
     */
    private function packFloat($f): string {
        $f1 = pack("f", $f);
        [, $f2] = unpack("L*", $f1);
        return pack("N", $f2);
    }
}
