<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Utility\ArrayUtils;

/**
 * Class ApiSelectMiddleWare A middleware to select which fields can be returned on an api v2 responses.
 *
 * @package Vanilla\Web
 */
class ApiSelectMiddleware
{
    /**
     * Filter out results by requested fields if there are any.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return Data
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        /** @var Data $response */
        $response = $next($request);
        $data = $response->getData();

        if (is_array($data)) {
            $requestedFields = [];
            $requestQuery = $request->getQuery();
            if ($requestQuery["fields"] ?? false) {
                $requestedFields = $requestQuery["fields"];
            }

            // If there were specifically requested fields, we filter the dataset accordingly.
            if (count($requestedFields) > 0) {
                // If the provided data is indexed data starting with index `0`.
                if (array_key_first($data) == "0") {
                    // We pluck out the wanted fields from every records.
                    foreach ($data as &$singleRecord) {
                        $singleRecord = $this->pluckSingleRecordFields($singleRecord, $requestedFields);
                    }
                } else {
                    $data = $this->pluckSingleRecordFields($data, $requestedFields);
                }
                $response->setData($data);
            }
        }

        return $response;
    }

    /**
     * Pluck a single record with a list of desired fields.
     *
     * @param array $record
     * @param array $requestedFields
     * @return array
     */
    private function pluckSingleRecordFields(array $record, array $requestedFields): array
    {
        // For compatibility with extended profile fields, we convert the data to dot notation.
        $dotNotationData = ArrayUtils::arrayToDotNotation($record);
        $dotNotationData = ArrayUtils::pluck($dotNotationData, $requestedFields);
        $pluckedRecord = ArrayUtils::dotNotationToArray($dotNotationData);

        // Compatibility for dot notation if the requested field is a parent.
        foreach ($requestedFields as $requestedField) {
            if (isset($record[$requestedField]) && !isset($pluckedRecord[$requestedField])) {
                $pluckedRecord[$requestedField] = $record[$requestedField];
            }
        }
        return $pluckedRecord;
    }
}
