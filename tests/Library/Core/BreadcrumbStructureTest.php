<?php
/**
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Provide tests to ensure the validity of breadcrumbs. This class will ensure
 * that all the breadcrumbs created by the core theme hooks contain valid data structure.
 *
 * Class BreadcrumbStructureTest
 * @package VanillaTests\Library\Core
 */
class BreadcrumbStructureTest extends SharedBootstrapTestCase {
    /**
     * Provide a test for {@link \Gdn_Theme::breadcrumbs()}
     *
     * @param array $data Typical data that would make up a breadcrumb.
     * @param bool $homeLink Whether to include the link to the homepage.
     * @param array $options Two options that could be passed to a breadcrumb.
     * @param string $expected HTML that would make up breadcrumbs with valid data structure.
     * @dataProvider provideBreadcrumbConfigsArray
     */
    public function testBreadcrumb(array $data, bool $homeLink, array $options, string $expected) {
        $breadCrumb = \Gdn_Theme::breadcrumbs($data, $homeLink, $options);
        $this->assertEquals($expected, $breadCrumb);
    }

    /**
     * Provide data for {@link \Gdn_Theme::breadcrumbs()}
     *
     * @return array Breadcrumb arguments and expected outcomes.
     */
    public function provideBreadcrumbConfigsArray() {
        $r = [
                [
                   [
                       ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories']
                   ],
                    true,
                    [],
                    '<span class="Breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">'.
                    '<span class="CrumbLabel HomeCrumb"><a href="http://vanilla.test/sharedbootstrap/"><span>Home</span></a></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">'.
                    '<meta itemprop="position" content="1" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel  Last"><a itemprop="item" href="http://vanilla.test/categories"><span itemprop="name">Categories</span></a></span></span></span>'
                ],
                [
                    [
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/one']
                    ],
                    true,
                    [],
                    '<span class="Breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">'.
                    '<span class="CrumbLabel HomeCrumb"><a href="http://vanilla.test/sharedbootstrap/"><span>Home</span></a></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><meta itemprop="position" content="1" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel  Last"><a itemprop="item" href="http://vanilla.test/categories/one"><span itemprop="name">Categories</span></a></span></span></span>'
                ],
                [
                    [
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/one'],
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/two']
                    ],
                    true,
                    [],
                    '<span class="Breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">'.
                    '<span class="CrumbLabel HomeCrumb"><a href="http://vanilla.test/sharedbootstrap/"><span>Home</span></a></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><meta itemprop="position" content="1" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel "><a itemprop="item" href="http://vanilla.test/categories/one"><span itemprop="name">Categories</span></a></span></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><meta itemprop="position" content="2" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel  Last"><a itemprop="item" href="http://vanilla.test/categories/two"><span itemprop="name">Categories</span></a></span></span></span>'
                ],
                [
                    [
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/one'],
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/two']
                    ],
                    false,
                    [],
                    '<span class="Breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">'.
                    '<span class="CrumbLabel "><a href="http://vanilla.test/categories/one"><span>Categories</span></a></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><meta itemprop="position" content="1" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel  Last"><a itemprop="item" href="http://vanilla.test/categories/two"><span itemprop="name">Categories</span></a></span></span></span>'
                ],
                [
                    [
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/one']
                    ],
                    false,
                    [],
                    '<span class="Breadcrumbs" ><span class="CrumbLabel  Last"><a href="http://vanilla.test/categories/one"><span>Categories</span></a></span></span>'
                ],
                [
                    [
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/one']
                    ],
                    true,
                    ['HomeUrl' => 'http://vanilla.test/en'],
                    '<span class="Breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList"><span class="CrumbLabel HomeCrumb"><a href="http://vanilla.test/en"><span>Home</span></a></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><meta itemprop="position" content="1" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel  Last"><a itemprop="item" href="http://vanilla.test/categories/one"><span itemprop="name">Categories</span></a></span></span></span>'
                ],
                [
                    [
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/one'],
                        ['Name' => 'Categories', 'Url' => 'http://vanilla.test/categories/two']
                    ],
                    true,
                    ['HideLast' => true],
                    '<span class="Breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList"><span class="CrumbLabel HomeCrumb"><a href="http://vanilla.test/sharedbootstrap/"><span>Home</span></a></span>'.
                    '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><meta itemprop="position" content="1" /><span class="Crumb">›</span> '.
                    '<span class="CrumbLabel  Last"><a itemprop="item" href="http://vanilla.test/categories/one"><span itemprop="name">Categories</span></a></span></span></span>'
                ]
            ];
        return $r;
    }
}

