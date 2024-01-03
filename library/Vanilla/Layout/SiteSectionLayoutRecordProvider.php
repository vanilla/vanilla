<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\Providers\LayoutViewRecordProviderInterface;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Subcommunities\SubcommunityLayoutRecordProvider;

/**
 * Provide for layout records of type "siteSection".
 *
 * Assignment of records is not supported in this provider.
 * See {@link SubcommunityLayoutRecordProvider} for an implementation that supports those assignments.
 */
class SiteSectionLayoutRecordProvider implements LayoutViewRecordProviderInterface
{
    public const RECORD_TYPE = "siteSection";

    protected SiteSectionModel $siteSectionModel;

    /**
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(SiteSectionModel $siteSectionModel)
    {
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Assignment of records is not supported in this provider.
     * See {@link SubcommunityLayoutRecordProvider} for an implementation that supports those assignments.
     *
     * For sites without subcommunity layouts can be assigned with {@link GlobalLayoutRecordProvider} or {@link CategoryLayoutRecordProvider}
     *
     * @inheritdoc
     */
    public function getRecords(array $recordIDs): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function validateRecords(array $recordIDs): bool
    {
        // We don't actually support assignment of these.
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array
    {
        return [self::RECORD_TYPE];
    }

    /**
     * Resolve a layout query using a site section.
     *
     * @inheritDoc
     */
    public function resolveLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        $siteSection = $this->siteSectionModel->getByID($query->recordID);
        return $query
            ->withRecordType($siteSection->getLayoutRecordType())
            ->withRecordID($siteSection->getLayoutRecordID());
    }

    /**
     * Resolves the global layout record provider.
     *
     * @inheritdoc
     */
    public function resolveParentLayoutQuery(LayoutQuery $query): LayoutQuery
    {
        return $query
            ->withRecordType(GlobalLayoutRecordProvider::RECORD_TYPE)
            ->withRecordID(GlobalLayoutRecordProvider::RECORD_ID);
    }
}
