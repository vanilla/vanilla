<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Gdn;
use Vanilla\Models\SiteTotalService;
use Vanilla\Utility\ArrayUtils;

/**
 * Filter for the openapi.
 */
class SiteTotalsFilterOpenApi
{
    /**
     * Add all the site total recordTypes to the openapi.
     *
     * @param array $openApi
     */
    public function __invoke(array &$openApi): void
    {
        $providers = Gdn::getContainer()
            ->get(SiteTotalService::class)
            ->getSiteTotalProviders();
        $countRecordTypes = [];
        $countRecordTypesResponseFields = [];
        foreach ($providers as $provider) {
            $recordType = $provider->getSiteTotalRecordType();
            $countRecordTypes[] = $recordType;
            $countRecordTypesResponseFields[$recordType] = ['$ref' => "#/components/schemas/Count"];
        }

        $currentCountRecordTypes = ArrayUtils::getByPath(
            "components.parameters.SiteTotalsCounts.schema.items.enum",
            $openApi,
            []
        );

        $countRecordTypes = array_merge($countRecordTypes, $currentCountRecordTypes);

        ArrayUtils::setByPath("components.parameters.SiteTotalsCounts.schema.items.enum", $openApi, $countRecordTypes);

        ArrayUtils::setByPath(
            "components.schemas.SiteTotalsCountsResponseFields.properties.counts.properties",
            $openApi,
            $countRecordTypesResponseFields
        );
    }
}
