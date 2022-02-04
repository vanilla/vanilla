<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout;

use Garden\Hydrate\DataHydrator;
use Monolog\Test\TestCase;
use Vanilla\Layout\LayoutHydrator;

trait LayoutTestTrait {
    /**
     * @return LayoutHydrator
     */
    private function getLayoutService(): LayoutHydrator {
        return self::container()->get(LayoutHydrator::class);
    }

    /**
     * Assert some layout spec hydrates to an expected value.
     *
     * @param array $hydrateSpec
     * @param array $params
     * @param array $expected
     * @param string|null $layoutViewType
     */
    public function assertHydratesTo(
        array $hydrateSpec,
        array $params,
        array $expected,
        ?string $layoutViewType = null
    ) {
        $hydrator = $this->getLayoutService()->getHydrator($layoutViewType);
        $actual = $hydrator->resolve($hydrateSpec, $params);
        TestCase::assertEquals($expected, $actual);
    }

    /**
     * Utility for creating a layout section.
     *
     * @param array $content
     * @param array $middleware
     *
     * @return array
     */
    protected function layoutSection(array $content, array $middleware = []): array {
        $node = [
            DataHydrator::KEY_HYDRATE => 'react.section.1-column',
            'contents' => $content,
        ];
        if (!empty($middleware)) {
            $node[DataHydrator::KEY_MIDDLEWARE] = $middleware;
        }
        return $node;
    }

    /**
     * Utility for creating a layout HTML widget.
     *
     * @param string $html
     * @param array $middleware
     *
     * @return string[]
     */
    protected function layoutHtml(string $html, array $middleware = []) {
        $node = [
            DataHydrator::KEY_HYDRATE => 'react.html',
            'html' => $html,
        ];

        if (!empty($middleware)) {
            $node[DataHydrator::KEY_MIDDLEWARE] = $middleware;
        }
        return $node;
    }
}
