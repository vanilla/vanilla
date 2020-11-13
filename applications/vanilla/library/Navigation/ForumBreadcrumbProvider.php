<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Navigation;

use Garden\StaticCacheTranslationTrait;
use Vanilla\Contracts\RecordInterface;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbProviderInterface;
use Vanilla\Site\SiteSectionModel;

/**
 * Breadcrumb provider for the forum application.
 */
class ForumBreadcrumbProvider implements BreadcrumbProviderInterface {

    use StaticCacheTranslationTrait;

    /** @var \CategoryModel */
    private $categoryModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var Breadcrumb[] */
    private $crumbsByCategoryID = [];

    /**
     * DI.
     *
     * @param \CategoryModel $categoryModel
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(\CategoryModel $categoryModel, SiteSectionModel $siteSectionModel) {
        $this->categoryModel = $categoryModel;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        $ancestors = $this->categoryModel->getAncestors($record->getRecordID());

        $crumbs = [
            new Breadcrumb(self::t('Home'), \Gdn::request()->url('/', true)),
        ];
        foreach ($ancestors as $ancestor) {
            $categoryID = $ancestor['CategoryID'];
            $existingCrumb = $this->crumbsByCategoryID[$categoryID] ?? null;
            if ($existingCrumb !== null) {
                $crumbs[] = $existingCrumb;
                continue;
            }

            if ($categoryID === -1) {
                // If we actually get the root category, we don't want to see the "synthetic" root.
                // We actually just want the categories page.

                // However, if the homepage is categories, we don't want to duplicate that either.
                if ($this->siteSectionModel->getCurrentSiteSection()->getDefaultRoute()['Destination'] === 'categories') {
                    continue;
                }

                $newCrumb = new Breadcrumb(t('Categories'), url('/categories', true));
            } else {
                $newCrumb = new Breadcrumb($ancestor['Name'], categoryUrl($ancestor, '', true));
            }
            $this->crumbsByCategoryID[$categoryID] = $newCrumb;
            $crumbs[] = $newCrumb;
        }
        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return [ForumCategoryRecordType::TYPE];
    }
}
