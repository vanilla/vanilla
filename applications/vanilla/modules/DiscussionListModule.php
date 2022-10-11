<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\JsonFilterTrait;
use Garden\Schema\Schema;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Exception\PermissionException;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;

/**
 * Class DiscussionListModule
 *
 * @package Vanilla\Forum\Modules
 */
class DiscussionListModule extends BaseDiscussionWidgetModule
{
    use JsonFilterTrait;

    /** @var bool */
    private $isMainContent = false;

    /**
     * @inheritDoc
     */
    public static function getApiSchema(): Schema
    {
        $apiSchema = new DiscussionsApiIndexSchema(10);
        $apiSchema->setField(
            "x-control",
            SchemaForm::section(new FormOptions("API Parameters", "Configure how the data is fetched."))
        );
        $apiSchema = $apiSchema->merge(Schema::parse([self::getSlotTypeSchema()]));
        return $apiSchema;
    }

    /**
     * @inheritdoc
     */
    public function getProps(?array $params = null): ?array
    {
        if ($this->discussions === null) {
            $apiParams = $this->getRealApiParams();
            try {
                $this->discussions = $this->discussionsApi->index($apiParams);
            } catch (PermissionException $e) {
                // A user might not have permission to see this.
                return null;
            }
        } else {
            // We don't know the API params, but make sure they were unique so the frontend doesn't mix them up.
            $apiParams = ["rand" => randomString(10)];
        }

        // Make sure our data gets the same filtering as if we had requested from the API.
        // Most notably this fixed up dates.
        $this->discussions = $this->jsonFilter($this->discussions);

        $props = [
            "apiParams" => $apiParams,
            "discussions" => $this->discussions,
            "title" => $this->title,
            "subtitle" => $this->subtitle,
            "description" => $this->description,
            "viewAllUrl" => $this->viewAllUrl,
            "isMainContent" => $this->isMainContent,
            "noCheckboxes" => !$this->isMainContent,
        ];

        return $props;
    }

    /**
     * Apply a full set of options from a shim.
     *
     * @param FoundationShimOptions $options
     */
    public function applyOptions(FoundationShimOptions $options)
    {
        $this->title = $options->getTitle();
        $this->description = $options->getDescription();
        $this->viewAllUrl = $options->getViewAllUrl();
    }

    /**
     * @param bool $isMainContent
     */
    public function setIsMainContent(bool $isMainContent): void
    {
        $this->isMainContent = $isMainContent;
    }
}
