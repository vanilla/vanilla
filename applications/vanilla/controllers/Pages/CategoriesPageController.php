<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Vanilla\Controllers\Pages;


use Garden\Web\Data;
use Vanilla\ApiUtils;
use Vanilla\Web\EbiView;

class CategoriesPageController {
    private $categoriesApi;

    private $discussionsApi;

    private $view;

    public function __construct(
        \CategoriesApiController $categoriesApi,
        \DiscussionsApiController $discussionsApi,
        EbiView $view

    ) {
        $this->categoriesApi = $categoriesApi;
        $this->discussionsApi = $discussionsApi;
        $this->view = $view;
    }

    public function index($urlCode = '', $page = '') {
        // Compatibility for root category displayAs: flat.
        if (empty($page) && preg_match('`^p\d+$`', $urlCode)) {
            $page = $urlCode;
            $urlCode = '';
        }

        $result = new Data();

        if (empty($urlCode)) {
            // This is the root category.
            $category = ApiUtils::convertOutputKeys(c('Vanilla.RootCategory', [])) + ['displayAs' => 'categories'];
            $parentCategoryID = null;
            $result->setMeta('title', t('All Categories'));
        } else {
            $category = $this->categoriesApi->get_urlCodes($urlCode);
            $parentCategoryID = $category['categoryID'];
            $result->setMeta('title', $category['name']);
        }
        $result['category'] = $category;

        $query = [
            'page' => ApiUtils::pageNumber($page),
            'expand' => true
        ];

        switch ($category['displayAs']) {
            case 'discussions':
                if (empty($urlCode)) {
                    trigger_error('The root category cannot be displayed as discussions.', E_USER_WARNING);
                }

                $query['categoryID'] = $parentCategoryID;
                $result->addData($this->discussionsApi->index($query), 'discussions', true);
                $result->addData(
                    $this->categoriesApi->index(['parentCategoryID' => $parentCategoryID, 'depth' => 1]),
                    'categories'
                );
                $result->setMeta('template', 'category-discussions-page');

                break;
            case 'flat':
                if (!empty($parentCategoryID)) {
                    $query['parentCategoryID'] = $parentCategoryID;
                }
                $query['depth'] = 1;
                $result->addData($this->categoriesApi->index($query), 'categories', true);
                $result->setMeta('template', 'category-flat-page');

                break;
            case 'categories':
            default:
                if (!empty($parentCategoryID)) {
                    $query['parentCategoryID'] = $parentCategoryID;
                }
                $query['depth'] = 3;
                $categories = $this->categoriesApi->index($query);
                $categories->setData($this->view->normalizeCategoryTree($categories->getData()));
                $result->addData($categories, 'categories', true);

                $result->setMeta('template', 'category-index-page');

                break;
        }
        $pagingCategory = ApiUtils::convertInputKeys($category);
        $result->addMeta('paging', 'urlFormat', categoryUrl($pagingCategory, '%s'));

        return $result;
    }
}
