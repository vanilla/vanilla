<?php

namespace Garden\Web;

use Vanilla\Schema\RangeExpression;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Class to control pagination logic
 */
class Pagination
{
    public string $urlFormat = "";

    public int $page = 1;

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
            $this->webLinking->addLink($rel, str_replace("%s", $page, $urlFormat));
        }
    }

    /**
     * Function to support cursor based pagination
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
        if (
            isset($query["page"]) &&
            isset($query[$primaryKeyField]) &&
            $query[$primaryKeyField] instanceof RangeExpression &&
            isset($query["sort"]) &&
            in_array($query["sort"], [$primaryKeyField, "-{$primaryKeyField}"])
        ) {
            $lastRecord = end($result);
            $min =
                $query["sort"] == $primaryKeyField
                    ? $lastRecord[$primaryKeyField] + 1
                    : $query["$primaryKeyField"]->getValue("[") ?? $query["$primaryKeyField"]->getValue("(");
            $max =
                $query["sort"] == $primaryKeyField
                    ? $query["$primaryKeyField"]->getValue("]") ?? $query["$primaryKeyField"]->getValue(")")
                    : $lastRecord[$primaryKeyField] - 1;

            if ($max < $min) {
                return $pagingResult;
            }

            //modify the url to include new min & max values
            $nextUrl = $paging["urlFormat"];
            $match = preg_match("~([?&]{$primaryKeyField}=)(\d+\.\.)(\d+)~", $paging["urlFormat"], $matches);
            if ($match == 1) {
                $nextUrl = str_replace($matches[0], $matches[1] . $min . ".." . $max, $nextUrl);
            }
            $paging["nextUrl"] = str_replace("%s", 1, $nextUrl);
            $pagingResult["paging"] = $paging;
        }
        return $pagingResult;
    }
}
