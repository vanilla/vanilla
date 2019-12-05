<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Twig\Environment;
use Twig\Error\SyntaxError;
use Vanilla\Web\TwigRenderTrait;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests to validate twig template.
 */
class TwigValidatorTest extends MinimalContainerTestCase {

    use TwigRenderTrait;

    private static $twigCache;

    /**
     * Get a twig instance.
     *
     * @return Environment
     */
    public function getTwig(): \Twig\Environment {
        if (!self::$twigCache) {
            self::$twigCache = $this->prepareTwig();
        }
        return self::$twigCache;
    }

    /**
     * Validate the syntax of all the twig templates.
     *
     * @param string $templatePath
     *
     * @dataProvider provideTemplatePaths
     */
    public function testValidateTemplates(string $templatePath) {
        $contents = file_get_contents($templatePath);
        try {
            $twig = $this->getTwig();
            $twig->parse($twig->tokenize(new \Twig\Source($contents, "template", $templatePath)));
            $this->assertSame(true, true);
        } catch (SyntaxError $e) {
            $shortPath = str_replace(PATH_ROOT, "", $templatePath);
            throw new \Exception("Failed to validate twig template at: " . $shortPath, 0, $e);
        }
    }

    /**
     * @return array
     */
    public function provideTemplatePaths(): array {
        $paramSets = [];

        $paths = glob(PATH_ROOT . "/{,*/,*/*/,*/*/*/}*.twig", \GLOB_BRACE);
        foreach ($paths as $path) {
            $shortPath = str_replace(PATH_ROOT, "", $path);
            $paramSets[$shortPath] = [$path];
        }
        return $paramSets;
    }
}
