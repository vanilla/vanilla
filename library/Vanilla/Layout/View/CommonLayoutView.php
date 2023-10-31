<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\View;

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Web\PageHeadInterface;

/**
 * Definition for assets and params common to every layoutViewType.
 */
class CommonLayoutView extends AbstractCustomLayoutView
{
    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var \CategoryModel */
    private $categoryModel;

    /**
     * DI.
     *
     * @param SiteSectionModel $siteSectionModel
     * @param \CategoryModel $categoryModel
     */
    public function __construct(SiteSectionModel $siteSectionModel, \CategoryModel $categoryModel)
    {
        $this->siteSectionModel = $siteSectionModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "common";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "common";
    }

    /**
     * @inheritdoc
     */
    public function getLayoutID(): string
    {
        return "common";
    }

    /**
     * @inheritdoc
     */
    public function getParamInputSchema(): Schema
    {
        return Schema::parse(["categoryID:i|s?", "siteSectionID:s?"]);
    }

    /**
     * @inheritdoc
     */
    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse([
            "category?" => $this->categoryModel->fragmentSchema(),
            "locale:s",
            "siteSection" => SiteSectionSchema::getSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $result = [];
        $siteSectionID = $paramInput["siteSectionID"] ?? null;
        $siteSection =
            $siteSectionID === null
                ? $this->siteSectionModel->getDefaultSiteSection()
                : $this->siteSectionModel->getByID($siteSectionID);
        if ($siteSectionID != 0) {
            $this->siteSectionModel->setCurrentSiteSection($siteSection);
        }
        $result["locale"] = $siteSection->getContentLocale();
        $result["siteSection"] = SiteSectionSchema::toArray($siteSection);

        $categoryID = $paramInput["categoryID"] ?? $siteSection->getCategoryID();
        $categoryID = $this->categoryModel->ensureCategoryID($categoryID);

        $result["categoryID"] = $categoryID;
        // Maybe null.
        $category = $this->categoryModel->getFragmentByID($categoryID);
        if ($category) {
            $result["category"] = $category;
        } else {
            throw new NotFoundException("Category", ["categoryID" => $category]);
        }

        $pageHead->setSeoTitle(
            Gdn::formatService()->renderPlainText(Gdn::config("Garden.HomepageTitle"), HtmlFormat::FORMAT_KEY),
            false
        );
        $pageHead->setSeoDescription(
            Gdn::formatService()->renderPlainText(Gdn::config("Garden.Description"), HtmlFormat::FORMAT_KEY)
        );
        $pageHead->setCanonicalUrl(\Gdn::request()->getSimpleUrl());
        return $result;
    }
}
