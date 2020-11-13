<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Html;

use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * HtmlDocument with assertion methods for tests.
 */
class TestHtmlDocument extends HtmlDocument {

    /**
     * @var string
     */
    protected $initialHtml;

    /**
     * @inheritdoc
     */
    public function __construct(string $innerHtml, ?bool $wrap = null) {
        $this->initialHtml = $innerHtml;
        if ($wrap === null) {
            $wrap = !str_starts_with($innerHtml, '<!DOCTYPE html>');
        }
        parent::__construct($innerHtml, $wrap);
    }

    /**
     * Return the RAW HTML as a string.
     *
     * @return string Returns an HTML string.
     */
    public function getRawHtml(): string {
        return $this->initialHtml;
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $cssSelector The CSS selector query.
     *
     * @param string $expected The expected text value.
     */
    public function assertCssSelectorText(string $cssSelector, string $expected) {
        $items = $this->queryCssSelector($cssSelector);
        $expected = trim($expected);
        foreach ($items as $item) {
            if (trim($item->textContent) == $expected) {
                TestCase::assertEquals($expected, trim($item->textContent));
                return $item;
            }
        }
        if (isset($item)) {
            TestCase::assertEquals($expected, $item->textContent);
        } else {
            TestCase::fail("Expected output not found.");
        }
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $cssSelector The CSS selector query.
     * @param string $expected The expected text value.
     * @return \DOMNode Returns the matching node for further processing.
     */
    public function assertCssSelectorTextContains(string $cssSelector, string $expected): \DOMNode {
        $items = $this->queryCssSelector($cssSelector);
        foreach ($items as $item) {
            if (strpos($item->textContent, $expected) !== false) {
                TestCase::assertStringContainsString($expected, $item->textContent);
                return $item;
            }
        }
        if (isset($item)) {
            TestCase::assertStringContainsString($expected, $item->textContent);
        } else {
            TestCase::fail("Expected output not found.");
        }
    }

    /**
     * Assert that some string is within the HTML somewhere.
     *
     * @param string $needle The needle to look for.
     * @param string $message An optional error message.
     */
    public function assertContainsString(string $needle, $message = ''): void {
        TestCase::assertStringContainsString($needle, $this->initialHtml, $message);
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $cssSelector The CSS selector query.
     * @param string $message An error message when the assertion fails.
     * @return \DOMNode The DomNode if found.
     */
    public function assertCssSelectorExists(string $cssSelector, string $message = ''): \DOMNode {
        $resultNode = $this->queryCssSelector($cssSelector)->item(0);
        TestCase::assertNotNull($resultNode, $message ?: "Nothing matched the provided XPath");
        return $resultNode;
    }

    /**
     * Assert that there is a form input with a given name and value.
     *
     * @param string $name The form input name.
     * @param string|null $value The desired value. Pass `null` to not assert and just return the node.
     * @return \DOMNode Returns the matched node.
     */
    public function assertFormInput(string $name, ?string $value = null): \DOMNode {
        $node = $this->assertCssSelectorExists("input[name=\"$name\"]", "Could not find form input: $name");
        if ($value !== null) {
            $attr = $node->getAttribute('value');
            TestCase::assertSame($value, $attr);
        }

        return $node;
    }

    /**
     * Assert that a css selector does not match.
     *
     * @param string $cssSelector The CSS selector query.
     *
     * @return void
     */
    public function assertCssSelectorNotExists(string $cssSelector): void {
        $resultNode = $this->queryCssSelector($cssSelector)->item(0);
        TestCase::assertNull($resultNode, "Nothing matched the provided XPath");
    }


    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $xpath The xpath query.
     * @see https://devhints.io/xpath For a cheatsheet.
     * @param string $expected The expected text value.
     */
    public function assertXPathText(string $xpath, string $expected) {
        $resultNode = $this->assertXPathExists($xpath);
        TestCase::assertEquals(trim($expected), trim($resultNode->textContent));
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $xpath The xpath query.
     * @see https://devhints.io/xpath For a cheatsheet.
     *
     * @return \DOMNode The DomNode if found.
     */
    public function assertXPathExists(string $xpath): \DOMNode {
        $resultNode = $this->queryXPath($xpath)->item(0);
        TestCase::assertNotNull($resultNode, "Nothing matched the provided XPath");
        return $resultNode;
    }

    /**
     * Make sure the document doesn't have duplicate IDs.
     */
    public function assertNoDuplicateIDs(): void {
        $ids = [];
        $nodes = $this->queryXPath('//*[@id]');
        foreach ($nodes as $node) {
            /** @var \DOMElement $node */
            $id = $node->getAttribute('id');
            TestCase::assertArrayNotHasKey($id, $ids, "Duplicate ID found in HTML: $id");
            $ids[$id] = $id;
        }
    }
}
