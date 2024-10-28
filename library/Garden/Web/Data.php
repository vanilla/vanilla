<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web;

use Garden\Http\HttpResponse;
use Garden\JsonFilterTrait;
use Gdn;
use Google\Api\Http;
use Traversable;

use Garden\MetaTrait;
use Vanilla\Http\InternalResponse;
use Vanilla\Utility\FileGeneratorUtils;
use Vanilla\Utility\StringUtils;
use Vanilla\Utility\UrlUtils;
use Vanilla\Web\JsonView;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Represents the data in a web response.
 */
class Data implements \JsonSerializable, \ArrayAccess, \Countable, \IteratorAggregate
{
    use MetaTrait, JsonFilterTrait;

    private $data;

    /** @var callable(array $value): array */
    private $beforeJsonSerialize;

    private $didApplyMetaHeaders = false;

    /**
     * Create a {@link Data} instance representing the data in a web response.
     *
     * @param mixed $data The main response data.
     * @param array|int $meta Either an array of meta information or an integer HTTP response status.
     * @param array $headers Headers to apply to the response.
     */
    public function __construct($data = [], $meta = [], $headers = [])
    {
        $this->data = $data;

        if (is_int($meta)) {
            $this->meta = ["status" => $meta];
        } else {
            $this->meta = $meta;
        }

        foreach ($headers as $headerKey => $header) {
            $this->setHeader($headerKey, $header);
        }
    }

    /**
     * Get a single item from the data array.
     *
     * @param string $name The key to get from.
     * @param mixed $default The default value if no item at the key exists.
     * @return mixed Returns the data value.
     */
    public function &getDataItem($name, $default = null)
    {
        if (!is_array($this->data) && !($this->data instanceof \ArrayAccess)) {
            throw new \Exception("Data is not an array.", 500);
        }
        $result = isset($this->data[$name]) ? $this->data[$name] : $default;
        return $result;
    }

    /**
     * Set a single item to the data array.
     *
     * @param string $name The key to set.
     * @param mixed $value The new value.
     * @return $this
     */
    public function setDataItem($name, $value)
    {
        if (!is_array($this->data) && !($this->data instanceof \ArrayAccess)) {
            throw new \Exception("Data is not an array.", 500);
        }
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Get the entire data payload.
     *
     * @return mixed Returns the data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get pagination info for the response.
     *
     * @return Pagination
     */
    public function getPaging(): Pagination
    {
        $pagination = new Pagination($this->getMeta("paging", []));
        return $pagination;
    }

    /**
     * Get the data wrapped with paging information for the frontend. Useful for preloading for react-query.
     *
     * @return array
     */
    public function withPaging(): array
    {
        return [
            "paging" => $this->getPaging(),
            "data" => $this,
        ];
    }

    /**
     * Set the entire data payload.
     *
     * @param mixed $data The new data array.
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Add another data object as a sub array of this data.
     *
     * @param array|Data $data The data to add.
     * @param string $key The key to add the data to.
     * @param bool $mergeMeta Whether or not to merge the meta array.
     * @return $this
     */
    public function addData($data, $key, $mergeMeta = false)
    {
        if (is_array($data)) {
            $this->data[$key] = $data;
        } else {
            $this->data[$key] = $data->getData();
            if ($mergeMeta) {
                $this->mergeMetaArray($data->getMetaArray());
            }
        }
        return $this;
    }

    /**
     * Merge another data array on top of this one.
     *
     * This method does a recursive merge so you can specify a deeply nested array here.
     *
     * @param array $data The data to merge.
     * @return $this
     */
    public function mergeData(array $data): self
    {
        $this->data = array_merge_recursive($this->data, $data);
        return $this;
    }

    /**
     * Get the HTTP status.
     *
     * @return int Returns the status.
     */
    public function getStatus()
    {
        $status = $this->getMeta("status", null);
        if ($status === null) {
            $status = $this->data === null ? 204 : 200;
        } elseif ($status < 100 || $status > 527) {
            $status = 500;
        }
        return $status;
    }

    /**
     * Set the HTTP status.
     *
     * @param int $status The new status.
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setMeta("status", $status);
    }

    /**
     * Register a callback to run before rendering JSON.
     *
     * @param callable $beforeJsonSerialize
     * @return void
     */
    public function hookJsonSerialize(callable $beforeJsonSerialize): void
    {
        $this->beforeJsonSerialize = $beforeJsonSerialize;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize()
    {
        $data = $this->getData();
        if (isset($this->beforeJsonSerialize)) {
            $data = call_user_func($this->beforeJsonSerialize, $data);
        }
        $data = $this->jsonFilter($data);
        return $data;
    }

    /**
     * Get a fully serialized version of the data.
     *
     * Primarily useful for making assertions about the data as it would come out of the API.
     *
     * @return mixed
     */
    public function getSerializedData()
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * Get this data as an HTTP response.
     *
     * @return InternalResponse
     */
    public function asHttpResponse(): InternalResponse
    {
        return new InternalResponse($this);
    }

    /**
     * Get a header value.
     *
     * @param string $name The name of the header.
     * @param mixed $default The default value if the header does not exist.
     * @return mixed Returns the header value or {@link $default}.
     */
    public function getHeader($name, $default = null)
    {
        return $this->getMeta($this->headerKey($name), $default);
    }

    /**
     * Set a header value.
     *
     * @param string $name The name of the header.
     * @param mixed $value The header value.
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->setMeta($this->headerKey($name), $value);
        return $this;
    }

    /**
     * Determine if a header exists.
     *
     * @param string $name The name of the header to check.
     * @return bool Returns **true** if the header exists or **false** otherwise.
     */
    public function hasHeader($name)
    {
        return isset($this->meta[$this->headerKey($name)]);
    }

    /**
     * Get all of the headers.
     *
     * @return array Returns the headers as an array.
     */
    public function getHeaders()
    {
        $result = [];

        foreach ($this->meta as $key => $value) {
            if ($key === "CONTENT_TYPE") {
                $result["Content-Type"] = $value;
            } elseif (substr_compare($key, "HTTP_", 0, 5, true) === 0) {
                $headerKey = $this->headerName(substr($key, 5));

                $result[$headerKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Normalize a header name into a header key.
     *
     * @param string $name The name of the header.
     * @return string Returns a string in the form **HTTP_***.
     */
    private function headerKey($name)
    {
        $key = strtoupper(str_replace("-", "_", $name));
        if ($key !== "CONTENT_TYPE") {
            $key = "HTTP_" . $key;
        }
        return $key;
    }

    /**
     * Normalize a header key into a header name.
     *
     * @param string $name The header name to normalize.
     * @return string Returns a string in the form "Header-Name".
     */
    private function headerName($name)
    {
        static $special = [
            "Md5" => "MD5",
            "Dnt" => "DNT",
            "Etag" => "ETag",
            "P3p" => "P3P",
            "Tsv" => "TSV",
            "Www" => "WWW",
        ];

        if (strpos($name, "-") !== false) {
            return $name;
        } else {
            $parts = explode("_", $name);

            $result = implode(
                "-",
                array_map(function ($part) use ($special) {
                    $r = ucfirst(strtolower($part));
                    return isset($special[$r]) ? $special[$r] : $r;
                }, $parts)
            );

            return $result;
        }
    }

    /**
     * Old rendering method that was only rendering json.
     *
     * Use renderJson() instead.
     *
     * @deprecated
     */
    public function render()
    {
        $this->renderJson();
    }

    /**
     * Render the response to the output in JSON.
     */
    public function renderJson()
    {
        $this->applyMetaHeaders();
        http_response_code($this->getStatus());

        if (!headers_sent()) {
            if (!$this->hasHeader("Content-Type")) {
                header("Content-Type: application/json; charset=utf-8", true);
            }
            foreach ($this->getHeaders() as $name => $value) {
                foreach ((array) $value as $line) {
                    header("$name: $line");
                }
            }
        }
        if (is_string($this->data) || $this->data === null) {
            echo $this->data;
        } else {
            echo json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    /**
     * Get CSV data of the response.
     *
     * @return string
     */
    private function getCsvData(): string
    {
        $data = $this->getSerializedData();
        if (!empty($data)) {
            return StringUtils::encodeCSV($data);
        } else {
            return "";
        }
    }

    /**
     * Render the response to the output in CSV.
     *
     * @codeCoverageIgnore
     */
    public function renderCsv()
    {
        if (!$this->isSuccessful()) {
            $this->renderJson();
            return;
        }

        $this->applyMetaHeaders();
        http_response_code($this->getStatus());

        if (!headers_sent()) {
            if (!$this->hasHeader("Content-Type")) {
                header("Content-Type: " . FileGeneratorUtils::getContentType("csv"), true);
            }

            if (!$this->hasHeader("Content-Disposition")) {
                header("Content-Disposition: " . FileGeneratorUtils::getContentDisposition(Gdn::request()), true);
            }
            foreach ($this->getHeaders() as $name => $value) {
                foreach ((array) $value as $line) {
                    header("$name: $line");
                }
            }
        }

        if (is_string($this->data) || $this->data === null) {
            echo $this->data;
        } else {
            echo $this->getCsvData();
        }
    }

    /**
     * Sometimes a middleware will be making use of a query parameter that will be stripped off
     * or not part of an endpoint's schema. As a result these parameter do not make it into
     * the pagination headers of the endpoint.
     *
     * In order to resolve this issue, a middleware may call this method on the response
     * and when the pagination headers are rendered they will include that query parameter.
     *
     * @param string $paramName
     * @param mixed $value
     * @return void
     */
    public function stashMiddlewareQueryParameter(string $paramName, $value): void
    {
        $this->setMeta("pagingQueryParams.{$paramName}", $value);
    }

    /**
     * Apply headers that come from meta items.
     */
    public function applyMetaHeaders()
    {
        if ($this->didApplyMetaHeaders) {
            return;
        }

        $this->didApplyMetaHeaders = true;
        $paging = $this->getMeta("paging");
        $pagingQueryParams = $this->getMeta("pagingQueryParams", []);

        // Handle pagination.
        if ($paging) {
            $pagingUri = \League\Uri\Http::createFromString($paging["urlFormat"]);

            if ($ext = $this->getMeta("extension")) {
                $pagingUri = $pagingUri->withPath("{$pagingUri->getPath()}.{$ext}");
            }

            if (!empty($pagingQueryParams)) {
                $pagingUri = UrlUtils::replaceQuery($pagingUri, $pagingQueryParams);
            }

            $paging["urlFormat"] = (string) $pagingUri;

            $pagination = new Pagination($paging);

            if (!empty($paging["cursor"])) {
                // Add an explicit next url to replace the generated one.
                $cursorNextUri = UrlUtils::replaceQuery($pagingUri, ["page" => null, "cursor" => $paging["cursor"]]);
                $pagination->nextUrl = (string) $cursorNextUri;
            }

            $links = $pagination->getPageLinks();
            $links->setHeader($this);
            $this->setHeader(JsonView::CURRENT_PAGE_HEADER, $paging["page"]);
            $totalCount = $paging["totalCount"] ?? null;
            if ($totalCount !== null) {
                $this->setHeader(JsonView::TOTAL_COUNT_HEADER, $totalCount);
            }
            $limit = $paging["limit"] ?? null;
            if ($limit !== null) {
                $this->setHeader(JsonView::LIMIT_HEADER, $limit);
            }
            $cursor = $paging["cursor"] ?? null;
            if (isset($cursor)) {
                $this->setHeader(JsonView::PAGE_CURSOR_HEADER, $cursor);
            }
        }
    }

    /**
     * Whether a offset exists.
     *
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function &offsetGet($offset)
    {
        return $this->getDataItem($offset);
    }

    /**
     * Offset to set.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value)
    {
        $this->setDataItem($offset, $value);
    }

    /**
     * Offset to unset.
     *
     * @param mixed $offset The offset to unset.
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Count elements of an object.
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Retrieve an external iterator.
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>.
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Box a value into a data object.
     *
     * If the argument is already a data object then it will simply be returned, otherwise a new data object is created
     * and returned with the argument as its data.
     *
     * @param Data|array $data The data to box.
     * @return Data Returns the boxed data.
     */
    public static function box($data): Data
    {
        if ($data instanceof Data) {
            return $data;
        } elseif (is_array($data)) {
            return new Data($data);
        } else {
            throw new \InvalidArgumentException("Data:box() expects an instance of Data or an array.", 500);
        }
    }

    /**
     * Check if the provided response matches the provided response type.
     *
     * The {@link $class} is a string representation of the HTTP status code, with 'x' used as a wildcard.
     *
     * Class '2xx' = All 200-level responses
     * Class '30x' = All 300-level responses up to 309
     *
     * @param string $class A string representation of the HTTP status code, with 'x' used as a wildcard.
     * @return boolean Returns `true` if the response code matches the {@link $class}, `false` otherwise.
     */
    public function isResponseClass(string $class): bool
    {
        $pattern = "`^" . str_ireplace("x", "\d", preg_quote($class, "`")) . '$`';
        $result = preg_match($pattern, $this->getStatus());

        return $result === 1;
    }

    /**
     * Determine if the response was successful.
     *
     * @return bool Returns `true` if the response was a successful 2xx code.
     */
    public function isSuccessful(): bool
    {
        return $this->isResponseClass("2xx");
    }
}
