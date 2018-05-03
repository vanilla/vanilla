<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize;

use Exception;
use Gdn;
use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Renderer;

abstract class SanitizeTest extends TestCase {

    /**
     * @param string $content
     * @return array
     */
    abstract protected function insertContentOperations(string $content): array;

    /**
     * Get the shared renderer instance.
     *
     * @return Renderer
     * @throws Exception if unable to retrieve an instance of the renderer.
     */
    protected function getRenderer(): Renderer {
        $class = Renderer::class;
        try {
            $renderer = Gdn::getContainer()->get($class);
        } catch (Exception $e) {
            throw new Exception("Unable to retrieve an instance of {$class}");
        }
        return $renderer;
    }

    /**
     * Provide bad strings for testing content sanitization.
     *
     * @return array
     */
    public function provideBadContent(): array {
        $result = [
            ["<script src=http://xss.rocks/xss.js></script>"],
            ["<script src=\"http://xss.rocks/xss.js\"></script>"],
            ["'';!--\"<xss>=&{()}"]
        ];
        return $result;
    }

    /**
     * @param string $badContents
     * @throws Exception if unable to retrieve an instance of the renderer.
     * @dataProvider provideBadContent
     */
    public function testSanitizeBadContent(string $badContents) {
        /** @var Renderer $renderer */
        $renderer = $this->getRenderer();
        $operations = $this->insertContentOperations($badContents);
        $result = $renderer->render($operations);

        // The contents should've been removed or encoded.
        $this->assertNotContains($badContents, $result);
    }
}
