<?php

namespace Garden\Web;

use League\Uri\Http;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\UrlUtils;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Class to control pagination logic
 */
class Pagination implements \JsonSerializable
{
    public string $urlFormat = "";

    public int $page = 1;

    public ?int $totalCount = null;

    public ?int $limit = null;

    public string $nextUrl = "";

    public bool $hasCount = false;

    private bool $more = false;

    public int $pageCount = 0;

    private WebLinking $webLinking;

    /**
     * class construct
     * @param array $paging
     */
    public function __construct(array $paging)
    {
        foreach ($paging as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
                if ($key == "pageCount" && !empty($val)) {
                    $this->hasCount = true;
                }
            }
        }
        $this->webLinking = new WebLinking();
    }

    public function jsonSerialize(): array
    {
        $pageLinks = $this->getPageLinks();
        return [
            "nextURL" => $pageLinks->getLinkUrl("next"),
            "prevURL" => $pageLinks->getLinkUrl("prev"),
            "currentPage" => $this->page,
            "total" => $this->totalCount,
            "limit" => $this->limit,
        ];
    }

    /**
     * Get pagination links
     * @return WebLinking
     */
    public function getPageLinks(): WebLinking
    {
        if (!$this->nextUrl) {
            $this->addPageLink("first", 1);
        }
        if ($this->page > 1 || $this->nextUrl) {
            $this->addPageLink("prev", !$this->nextUrl ? $this->page - 1 : 1);
        }
        if ($this->more || ($this->hasCount && $this->page < $this->pageCount)) {
            $this->addPageLink("next", $this->page + 1);
        }
        if (!empty($this->pageCount)) {
            $this->addPageLink("last", $this->pageCount);
        }
        return $this->webLinking;
    }

    /**
     * Add new web links
     * @param string $rel
     * @param int $page
     * @return void
     */
    private function addPageLink(string $rel, int $page): void
    {
        $urlFormat = $this->urlFormat;
        if ($rel == "next" && !empty($this->nextUrl)) {
            $this->webLinking->addLink("next", $this->nextUrl);
            return;
        }
        if (!empty($urlFormat)) {
            $this->webLinking->addLink($rel, str_replace(["%s", "%25s"], $page, $urlFormat));
        }
    }

    /**
     * Attempt to use a primary key based cursor for pagination on an endpoint.
     *
     * This only works if:
     *
     * - The endpoint supports filtering by a {@link RangeExpression} on it's primary key.
     * - The endpoint supports sorting ASC and DESC on it's primary key.
     * - We are paginating forwards. Backwards pagination was not implemented.
     *
     * @param array $paging
     * @param array $query
     * @param array $result
     * @param string $primaryKeyField
     * @return array
     */
    public static function tryCursorPagination(
        array $paging,
        array $query,
        array $result,
        string $primaryKeyField
    ): array {
        $pagingResult = ["paging" => $paging];
        if (!count($paging) || empty($result) || (isset($query["limit"]) && count($result) < $query["limit"])) {
            return $pagingResult;
        }
        $primaryKeyIsRangeOrEmpty =
            !isset($query[$primaryKeyField]) || $query[$primaryKeyField] instanceof RangeExpression;
        $sortIsPrimaryKey =
            isset($query["sort"]) && in_array($query["sort"], [$primaryKeyField, "-{$primaryKeyField}"]);
        $lastRecord = end($result);

        if ($primaryKeyIsRangeOrEmpty && $sortIsPrimaryKey && $lastRecord) {
            $primaryKeyRange = $query[$primaryKeyField] ?? null;

            if ($query["sort"] === $primaryKeyField) {
                // We are sorting ASC so we have a clear new minimum
                $newMinID = $lastRecord[$primaryKeyField] + 1;
                if ($primaryKeyRange instanceof RangeExpression) {
                    // If we have an upper end from the initial query preserve that.
                    $newPrimaryKeyRange = $primaryKeyRange->withFilteredValue(">=", $newMinID);
                } else {
                    // We just have a minimum value.
                    $newPrimaryKeyRange = new RangeExpression(">=", $newMinID);
                }
            } else {
                // We are sorting DESC so we have a new clear upper bound.
                $newMaxID = $lastRecord[$primaryKeyField] - 1;
                if ($primaryKeyRange instanceof RangeExpression) {
                    // If we have an upper end from the initial query preserve that.
                    $newPrimaryKeyRange = $primaryKeyRange->withFilteredValue("<=", $newMaxID);
                } else {
                    // We just have a minimum value.
                    $newPrimaryKeyRange = new RangeExpression("<=", $newMaxID);
                }
            }

            // modify the url to include the new range
            // Notably when we do this "prev" urls stop appearing as that has not been implemented yet.
            $nextUrl = $paging["urlFormat"];
            $paging["urlFormat"] = (string) UrlUtils::replaceQuery(Http::createFromString($nextUrl), [
                $primaryKeyField => (string) $newPrimaryKeyRange,
                "page" => 1, // Page resets to 1.
            ]);

            $pagingResult["paging"] = $paging;
        }
        return $pagingResult;
    }
}
