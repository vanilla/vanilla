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

/**
 * Breadcrumb provider for the forum application.
 */
class ForumBreadcrumbProvider implements BreadcrumbProviderInterface {

    use StaticCacheTranslationTrait;

    /** @var \CategoryCollection */
    private $categoryCollection;

    /**
     * @param \CategoryCollection $categoryCollection
     */
    public function __construct(\CategoryCollection $categoryCollection) {
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        $ancestors = $this->categoryCollection->getAncestors($record->getRecordID());

        $crumbs = [
            new Breadcrumb(self::t('Community'), \Gdn::request()->url('/')),
        ];
        foreach ($ancestors as $ancestor) {
            $crumbs[] = new Breadcrumb($ancestor['Name'], categoryUrl($ancestor));
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
