<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use AbstractApiController;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\Models\SiteSectionTotalProviderInterface;
use Vanilla\Models\SiteTotalService;
use Vanilla\Site\SiteSectionModel;

/**
 * /api/v2/site-totals
 */
class SiteTotalsApiController extends AbstractApiController
{
    /** @var SiteTotalService */
    private $siteTotalService;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * DI.
     *
     * @param SiteTotalService $siteTotalService
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(SiteTotalService $siteTotalService, SiteSectionModel $siteSectionModel)
    {
        $this->siteTotalService = $siteTotalService;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Get the total counts for a site (or, optionally, a site section).
     *
     * @param array $query
     * @return Data
     * @throws NotFoundException Throws an exception if the siteSection is not found.
     */
    public function index(array $query)
    {
        // No permission check.
        $siteSection = null;
        if ($query["siteSectionID"] ?? null) {
            $siteSection = $this->siteSectionModel->getByID($query["siteSectionID"]);
            if ($siteSection === null) {
                throw new NotFoundException("Site section");
            }
        }

        $schema = $this->siteTotalService->getCountsQuerySchema();
        $validatedQuery = $schema->validate($query);
        $result = $siteSection ? ["siteSectionID" => $query["siteSectionID"]] : [];
        if (in_array("all", $query["counts"])) {
            $validatedQuery["counts"] = $this->siteTotalService->getCountRecordTypes();
        }
        foreach ($validatedQuery["counts"] as $countItem) {
            $count = $this->siteTotalService->getTotalForType($countItem, $siteSection ?? null);
            $result["counts"][$countItem]["count"] = $count;
            $result["counts"][$countItem]["isCalculating"] = $count === -1;
            $result["counts"][$countItem]["isFiltered"] =
                $siteSection !== null &&
                $this->siteTotalService->getSiteTotalProviders()[$countItem] instanceof
                    SiteSectionTotalProviderInterface;
        }

        $out = $this->siteTotalService->getCountsOutputSchema();
        $validatedResult = $out->validate($result);
        return new Data($validatedResult);
    }
}
