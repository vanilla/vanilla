<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Formatting;

/**
 * Class for fetching content of fixtures in the body.
 *
 * - input: input.*
 * - html output: output.html
 * - text output: output.txt
 * - excerpt output: output-excerpt.txt
 * - parsed mentions: output-mentions.json
 * - parsed headings: output-headings.json
 * - quote output: output-quote.html
 */
class FormatFixture {

    /** @var string */
    private $fixtureRoot;

    /**
     * @param string $fixtureRoot
     */
    public function __construct(string $fixtureRoot) {
        $this->fixtureRoot = $fixtureRoot;
    }

    /**
     * Get string contents of a fixture if it exists..
     *
     * @param string $fileName
     * @return string|null
     */
    private function getFixtureContentsWithFileName(string $fileName): ?string {
        $fullPath = $this->fixtureRoot . '/' . $fileName;
        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return basename($this->fixtureRoot);
    }

    /**
     * @return string
     */
    public function getInput(): string {
        $inputs = glob($this->fixtureRoot . '/input.*');
        return $this->getFixtureContentsWithFileName(basename($inputs[0]));
    }

    /**
     * @return string
     */
    public function getHtml(): ?string {
        return $this->getFixtureContentsWithFileName('output.html');
    }

    /**
     * @return string|null
     */
    public function getText(): ?string {
        return $this->getFixtureContentsWithFileName('output.txt');
    }

    /**
     * @return string|null
     */
    public function getExcerpt(): ?string {
        return $this->getFixtureContentsWithFileName('output-excerpt.txt');
    }

    /**
     * @return string|null
     */
    public function getQuote(): ?string {
        return $this->getFixtureContentsWithFileName('output-quote.html');
    }

    /**
     * @return array|null
     */
    public function getHeadings(): ?array {
        $json = $this->getFixtureContentsWithFileName('output-headings.json');
        if ($json) {
            return json_decode($json, true);
        }

        return null;
    }

    /**
     * @return array|null
     */
    public function getMentions(): ?array {
        $json = $this->getFixtureContentsWithFileName('output-mentions.json');
        if ($json) {
            return json_decode($json, true);
        }

        return null;
    }
}
