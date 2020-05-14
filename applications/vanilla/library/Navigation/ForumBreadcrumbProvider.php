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

    /** @var \CategoryCollection */
    private $categoryCollection;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * @param \CategoryCollection $categoryCollection
     */
    public function __construct(\CategoryCollection $categoryCollection, SiteSectionModel $siteSectionModel) {
        $this->categoryCollection = $categoryCollection;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        $ancestors = $this->categoryCollection->getAncestors($record->getRecordID());

        $crumbs = [
            new Breadcrumb(self::t('Home'), \Gdn::request()->url('/')),
        ];
        foreach ($ancestors as $ancestor) {
            if ($ancestor['CategoryID'] === -1) {
                // If we actually get the root category, we don't want to see the "synthetic" root.
                // We actually just want the categories page.

                // However, if the homepage is categories, we don't want to duplicate that either.
                if ($this->siteSectionModel->getCurrentSiteSection()->getDefaultRoute()['Destination'] === 'categories') {
                    continue;
                };

                $crumbs[] = new Breadcrumb(t('Categories'), url('/categories'));
            } else {
                $crumbs[] = new Breadcrumb($ancestor['Name'], categoryUrl($ancestor));
            }
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
