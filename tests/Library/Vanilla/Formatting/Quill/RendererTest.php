<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Filterer;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\Formatting\Quill\Renderer;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for the Quill Renderer.
 */
class RendererTest extends SharedBootstrapTestCase {

    use AssertsFixtureRenderingTrait;

    /**
     * Render a given set of operations.
     *
     * @param array $ops The operations to render.
     *
     * @return string
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    protected function render(array $ops): string {
        $filterer = \Gdn::getContainer()->get(Filterer::class);
        $renderer = \Gdn::getContainer()->get(Renderer::class);
        $parser = \Gdn::getContainer()->get(Parser::class);

        // We need to filter to test properly.
        $filteredValue = json_decode($filterer->filter(json_encode($ops)), true);
        return $renderer->render($parser->parse($filteredValue));
    }

    /**
     * Full E2E tests for the Quill rendering.
     *
     * @param string $dirname The directory name to get fixtures from.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @dataProvider provideHtml
     */
    public function testRender(string $dirname) {
        list($input, $expectedOutput) = $this->getFixture(
            $dirname
        );
        $json = \json_decode($input, true);

        $output = $this->render($json);
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput,
            $output,
            "Expected html outputs for fixture $dirname did not match."
        );
    }

    /**
     * Provide inputs and expected HTML outputs.
     *
     * @return array
     */
    public function provideHtml() {
        $res = $this->createFixtureDataProvider('/formats/rich/html');
        return $res;
    }
}
