<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Storybook;

use Nette\Utils\FileSystem;
use Vanilla\Formatting\Html\HtmlDocument;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Base test case for generating storybook data.
 */
abstract class StorybookGenerationTestCase extends AbstractAPIv2Test {

    const STORYBOOK_BASE_PATH = PATH_ROOT . '/build/.storybookAppPages';

    /** @var bool */
    private static $prettierExists = false;

    /**
     * @return string
     */
    protected static function getBootstrapFolderName() {
        return "";
    }


    /**
     * Generate story HTML from an application URL.
     *
     * @param string $url
     * @param string $storyName
     */
    public function generateStoryHtml(string $url, string $storyName) {
        $htmlDocument = $this->bessy()->getHtml($url, [
            'deliveryType' => DELIVERY_TYPE_ALL,
        ]);

        // Strip out some elements we don't use.
        $noscripts = $htmlDocument->queryCssSelector("noscript");
        /** @var \DOMNode $noscript */
        foreach ($noscripts as $noscript) {
            $noscript->parentNode->removeChild($noscript);
        }

        // Modify the HTML
        /** @var \DOMElement $bodyNode */
        $bodyNode = $htmlDocument->queryXPath("//html/body")->item(0);
        $this->assertInstanceOf(\DOMElement::class, $bodyNode);

        $cssFiles = $this->extractLegacyCssFiles($htmlDocument);
        $this->assertNotEmpty($cssFiles, "Something went wrong extracting the page's legacy CSS files.");
        $bodyClasses = $bodyNode->getAttribute("class") ?? "";
        $data = [
            'cssFiles' => $cssFiles,
            'bodyClasses' => $bodyClasses,
        ];
        $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $writeBase = self::STORYBOOK_BASE_PATH . '/' . $storyName;
        $htmlPath = $writeBase . '.html';
        $dataPath = $writeBase . '.json';

        $bodyContent = $htmlDocument->getOuterHtml($bodyNode);
        FileSystem::write($htmlPath, $bodyContent, 0777);
        FileSystem::write($dataPath, $dataJson, 0777);

        // We should have prettier already from our node installation. Format the resulting file with it if possible.
        if ($this->prettierExists()) {
            exec("yarn prettier --write \"$htmlPath\"");
        }
    }

    /**
     * @return bool
     */
    private function prettierExists(): bool {
        if (!isset(self::$prettierExists)) {
            self::$prettierExists = file_exists(PATH_ROOT . "/node_modules");
        }
        return self::$prettierExists;
    }

    /**
     * Excra
     *
     * @param HtmlDocument $htmlDocument
     * @return string[]
     */
    private function extractLegacyCssFiles(HtmlDocument $htmlDocument): array {
        $result = [];
        /** @var \DOMElement $headNode */
        $cssLinks = $htmlDocument->queryXPath("//html/head/link[@rel=\"stylesheet\"]");

        /** @var \DOMElement $cssLink */
        foreach ($cssLinks as $cssLink) {
            $href = $cssLink->getAttribute('href') ?? '';
            $normalizedHref = $this->getNormalizedCssPath($href);
            if ($normalizedHref) {
                $result[] = $normalizedHref;
            }
        }

        return $result;
    }

    /**
     * Attempt to normalize a css file path.
     *
     * @param string $initialPath
     * @return string|null
     */
    private function getNormalizedCssPath(string $initialPath): ?string {
        $path = parse_url($initialPath, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        // Make sure we "legacy" CSS. Eg. from a design folder.
        if (!str_contains($path, "/design/")) {
            return null;
        }

        // we should now have a filepath. Validate that it actually exists.
        $filePath = PATH_ROOT . $path;
        if (!file_exists($filePath)) {
            return null;
        }

        return $path;
    }
}
