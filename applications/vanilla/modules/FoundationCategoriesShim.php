<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\Container\Container;
use Vanilla\Web\TwigStaticRenderer;

/**
 * Class for converting legacy category items into new modules.
 */
class FoundationCategoriesShim {

    private const TYPE_CATEGORIES = 'categories';
    private const TYPE_HEADING = 'heading';

    /** @var \CategoryModel */
    private $categoryModel;

    /** @var Container */
    private $container;

    /**
     * DI.
     *
     * @param \CategoryModel $categoryModel
     * @param Container $container
     */
    public function __construct(\CategoryModel $categoryModel, Container $container) {
        $this->categoryModel = $categoryModel;
        $this->container = $container;
    }

    /**
     * Render a list of legacy style category data into home widgets.
     *
     * @param array $legacyCategories
     * @return string
     */
    public function renderShimLegacyCategories(array $legacyCategories): string {
        $groups = [];
        $currentGroup = FoundationCategoriesShim::createGroup();

        // Utility method to clear the current grouping.
        $clearGroup = function () use (&$currentGroup, &$groups) {
            if (!empty($currentGroup['items'])) {
                $groups[] = $currentGroup;
                $currentGroup = FoundationCategoriesShim::createGroup();
            }
        };

        $categories = [];
        foreach ($legacyCategories as $legacyCategory) {
            $categories[] = $this->categoryModel->normalizeRow($legacyCategory);
        }

        $parseChildren = function (array $children, int $level = 2) use (&$currentGroup, &$groups, &$clearGroup, &$parseChildren) {
            // Break up the categories with headings as separators.
            // After each heading we have a separate grid.
            foreach ($children as $category) {
                if ($category['displayAs'] === 'heading') {
                    $clearGroup();
                    $groups[] = [
                        'type' => self::TYPE_HEADING,
                        'name' => $category['name'],
                        'level' => $level,
                    ];
                    $parseChildren($category['children'], $level + 1);
                    $clearGroup();
                } else {
                    $currentGroup['items'][] = self::mapApiCategoryToItem($category);
                }
            }
        };

        $parseChildren($categories);

        // Clear the remaining group if there is one.
        $clearGroup();

        $result = '';
        // Render all groups.
        foreach ($groups as $group) {
            if ($group['type'] === self::TYPE_HEADING) {
                $result .= TwigStaticRenderer::renderTwigStatic("@vanilla/categories/categoryBoxHeading.twig", $group);
            }

            if (!empty($group['items'])) {
                // Headings can sometimes have children. Since they aren't navigable, display them here.
                /** @var FoundationCategoriesGridModule $shimModule */
                $shimModule = $this->container->get(FoundationCategoriesGridModule::class);
                $shimModule->setWidgetItems($group['items']);
                $result .= $shimModule->toString();
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    private static function createGroup(): array {
        return [
            'type' => self::TYPE_CATEGORIES,
            'items' => [],
        ];
    }

    /**
     * Static utility to drop in old rendering places.
     *
     * @param array $legacyCategories
     */
    public static function printLegacyShim(array $legacyCategories) {
        /** @var FoundationCategoriesShim $shim */
        $shim = \Gdn::getContainer()->get(FoundationCategoriesShim::class);
        echo $shim->renderShimLegacyCategories($legacyCategories);
    }

    /**
     * Static utility to see if we should render with the shim or not.
     *
     * @return bool
     */
    public static function isEnabled(): bool {
        return \Gdn::config('Vanilla.Categories.Layout') === 'foundation';
    }


    /**
     * Utility for for mapping category data into a widget item.
     *
     * @param array $category
     * @return array
     */
    public static function mapApiCategoryToItem(array $category): array {
        return [
            'to' => $category['url'],
            'iconUrl' => $category['iconUrl'] ?? null,
            'imageUrl' => $category['bannerUrl'] ?? null,
            'name' => $category['name'],
            'description' => $category['description'] ?? '',
            'counts' => [
                [
                    'labelCode' => 'Discussions',
                    'count' => $category['countAllDiscussions'] ?? 0,
                ]
            ]
        ];
    }
}
