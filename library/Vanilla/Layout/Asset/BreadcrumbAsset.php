<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Asset;

use Garden\Schema\Schema;
use Vanilla\Contracts\RecordInterface;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Models\GenericRecord;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Web\BreadcrumbJsonLD;

/**
 * Asset representing breadcrumbs for the page.
 */
class BreadcrumbAsset extends AbstractLayoutAsset {

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /**
     * DI.
     *
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(BreadcrumbModel $breadcrumbModel) {
        $this->breadcrumbModel = $breadcrumbModel;
    }

    ///
    /// region Naming
    ///

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return "Breadcrumbs";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return "Breadcrumbs";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string {
        return "asset.breadcrumbs";
    }

    ///
    /// endregion
    ///
    /// region Schema & Props
    ///

    /**
     * @inheritdoc
     */
    public function getProps(): ?array {
        $recordType = $this->props['recordType'] ?? null;
        $recordID = $this->props['recordID'] ?? null;
        if ($recordType === null || $recordID === null) {
            return null;
        }

        $record = new GenericRecord($recordType, $recordID);
        $crumbs = $this->breadcrumbModel->getForRecord($record);

        $includeHomeCrumb = $this->props['includeHomeCrumb'];
        if (!$includeHomeCrumb) {
            array_shift($crumbs);
        }
        if ($this->pageHead !== null) {
            $this->pageHead->setSeoBreadcrumbs($crumbs);
        }
        return [
            'crumbs' => $crumbs
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'recordType:s?' => 'The record type of the record to build breadcrumbs for.',
            'recordID:i?' => 'The record type of the record to build breadcrumbs for.',
            'includeHomeCrumb:b?' => [
                'description' => 'Set this false to `true` to include a home breadcrumb.',
                'default' => true,
            ],
        ]);
    }

    /// endregion
}
