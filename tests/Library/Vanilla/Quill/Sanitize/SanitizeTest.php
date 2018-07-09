<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize;

use Exception;
use Gdn;
use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Parser;
use Vanilla\Quill\Renderer;
use VanillaTests\BootstrapTrait;

abstract class SanitizeTest extends TestCase {
    use BootstrapTrait;

    abstract protected function insertContentOperations(string $content): array;

    /**
     * Render a given set of operations.
     *
     * @param array $ops The operations to render.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    protected function render(array $ops): string {
        $renderer = Gdn::getContainer()->get(Renderer::class);
        $parser = Gdn::getContainer()->get(Parser::class);

        return $renderer->render($parser->parse($ops));
    }

    /**
     * Provide bad strings for testing content sanitization.
     *
     * @return array
     */
    public function provideBadContent(): array {
        $result = [
            ["<script>"],
            ["<script src=http://xss.rocks/xss.js></script>"],
            ["<script src=\"http://xss.rocks/xss.js\"></script>"],
            ["'';!--\"<xss>=&{()}"]
        ];
        return $result;
    }

    /**
     * Test sanitizing the content of a blot.
     *
     * @dataProvider provideBadContent
     */
    public function testSanitizeBadContent(string $badContents) {
        $operations = $this->insertContentOperations($badContents);
        // The contents should've been removed or encoded.
        $this->assertSanitized($operations, $badContents);
    }

    /**
     * Assert that a the contents of certain operations will be properly sanitized.
     *
     * @param array $operations The operations to render.
     * @param string $badValue The value that should not appear in the rendered output.
     *
     * @throws Exception If the parser or render could not be instantiated.
     */
    protected function assertSanitized(array $operations, string $badValue) {
        $result = $this->render($operations);

        // The contents should've been removed or encoded.
        $this->assertNotContains($badValue, $result);
    }
}
