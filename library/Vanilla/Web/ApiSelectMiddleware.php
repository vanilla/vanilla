<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;

/**
 * Middleware to rewrite response output to filter, flatten, and relabel various fields.
 *
 * @example
 * Let's say we have an endpoint `/api/v2/users` that returns the following data:
 * ```json
 * [{
 *      "userID": 10,
 *      "name": "Johny",
 *      "email": "john@test.com",
 *      "profileFields": {
 *          "myField": "Foobar"
 *      }
 * }]
 * ```
 *
 * If the endpoint were called like
 * GET /api/v2/users?fieldMapping[userID]=User ID&fieldMapping[name]=User Name&fieldMapping[profileFields.myField]=My Awesome Field&fieldMapping[fake]=Doesn't Exist
 *
 * Results will be
 * ```json
 * [{
 *      "User ID": 10,
 *      "User Name": "Johhny",
 *      "My Awesome Field": "Foobar",
 *      "Doesn't Exist": null
 * }]
 */
class ApiSelectMiddleware
{
    /**
     * Filter out results by requested fields if there are any.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return Data
     * @throws ValidationException
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        /** @var Data $response */
        $response = $next($request);
        $data = $response->getData();

        if (is_array($data) && $response->isSuccessful()) {
            $requestQuery = $request->getQuery();
            $querySchema = Schema::parse([
                "fieldMapping:o?",
                "fields:a?" => [
                    "items" => [
                        "type" => "string",
                    ],
                    "style" => "form",
                ],
            ])->addValidator("", SchemaUtils::onlyOneOf(["fields, fieldMapping"]));
            $query = $querySchema->validate($requestQuery);
            $fieldMapping = $query["fieldMapping"] ?? null;
            $fields = $query["fields"] ?? null;
            if ($fieldMapping === null && $fields !== null) {
                $fieldMapping = [];
                foreach ($fields as $field) {
                    $fieldMapping[$field] = $field;
                }
            }

            // If there were specifically requested fields, we filter the dataset accordingly.
            if (is_array($fieldMapping) && count($fieldMapping) > 0) {
                $response->setMeta(CsvView::META_HEADINGS, array_values($fieldMapping));
                $response->stashMiddlewareQueryParameter("fieldMapping", $fieldMapping);

                if (ArrayUtils::isAssociative($data)) {
                    $response->setData($this->mapRow($data, $fieldMapping));
                } else {
                    foreach ($data as &$singleRecord) {
                        $singleRecord = $this->mapRow($singleRecord, $fieldMapping);
                    }
                    $response->setData($data);
                }
            }
        }

        return $response;
    }

    /**
     * Given a mapping of fieldName => label name, select out just the fields and label them appropriately.
     *
     * @param array $row
     * @param array $selectFields
     * @return array
     */
    private function mapRow(array $row, array $selectFields): array
    {
        $result = [];
        foreach ($selectFields as $fieldName => $fieldLabel) {
            $result[$fieldLabel] = ArrayUtils::getByPath($fieldName, $row, null);
        }
        return $result;
    }
}
