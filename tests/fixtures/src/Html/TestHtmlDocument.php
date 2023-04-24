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
class TestHtmlDocument extends HtmlDocument
{
    /**
     * @var string
     */
    protected $initialHtml;

    /**
     * @inheritdoc
     */
    public function __construct(string $innerHtml, ?bool $wrap = null)
    {
        $this->initialHtml = $innerHtml;
        if ($wrap === null) {
            $wrap = !str_starts_with($innerHtml, "<!DOCTYPE html>");
        }
        parent::__construct($innerHtml, $wrap);
    }

    /**
     * Return the RAW HTML as a string.
     *
     * @return string Returns an HTML string.
     */
    public function getRawHtml(): string
    {
        return $this->initialHtml;
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $cssSelector The CSS selector query.
     *
     * @param string $expected The expected text value.
     */
    public function assertCssSelectorText(string $cssSelector, string $expected)
    {
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
    public function assertCssSelectorTextContains(string $cssSelector, string $expected): \DOMNode
    {
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
     * Assert that IF a CSS selector exists,. it does not contain a string.
     *  Good for making sure the correct error message is displaying
     *
     * @param string $cssSelector The CSS selector query.
     * @param string $expected The expected text value.
     * @return void Does not return an error if it is not present.
     */
    public function assertCssSelectorNotTextContains(string $cssSelector, string $expected)
    {
        $items = $this->queryCssSelector($cssSelector);
        foreach ($items as $item) {
            if (isset($item)) {
                TestCase::assertStringNotContainsString($expected, $item->textContent);
                return $item;
            }
        }
        TestCase::assertStringNotContainsString($expected, "");
    }

    /**
     * Assert that some string is within the HTML somewhere.
     *
     * @param string $needle The needle to look for.
     * @param string $message An optional error message.
     */
    public function assertContainsString(string $needle, $message = ""): void
    {
        TestCase::assertStringContainsString($needle, $this->initialHtml, $message);
    }

    /**
     * Assert that some string is not within the HTML somewhere.
     *
     * @param string $needle The needle to look for.
     * @param string $message An optional error message.
     */
    public function assertNotContainsString(string $needle, $message = ""): void
    {
        TestCase::assertStringNotContainsString($needle, $this->initialHtml, $message);
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $cssSelector The CSS selector query.
     * @param string $message An error message when the assertion fails.
     * @return \DOMElement The DomNode if found.
     */
    public function assertCssSelectorExists(string $cssSelector, string $message = ""): \DOMNode
    {
        $resultNode = $this->queryCssSelector($cssSelector)->item(0);
        TestCase::assertNotNull($resultNode, $message ?: "Nothing matched the provided XPath");
        return $resultNode;
    }

    /**
     * Assert that there is a form input with a given name and value.
     *
     * @param string $name The form input name.
     * @param string|null $value The desired value. Pass `null` to not assert and just return the node.
     * @return \DOMElement Returns the matched node.
     */
    public function assertFormInput(string $name, ?string $value = null): \DOMElement
    {
        $node = $this->assertCssSelectorExists("input[name=\"$name\"]", "Could not find form input: $name");
        if ($value !== null) {
            $attr = $node->getAttribute("value");
            TestCase::assertSame($value, $attr);
        }

        return $node;
    }

    /**
     * Assert that a form input does not exist in the document.
     *
     * @param string $name The form input name.
     */
    public function assertNoFormInput(string $name): void
    {
        $results = $this->queryCssSelector("input[name=\"$name\"]");
        if ($results->count() > 0) {
            TestCase::fail("The form input should not exist: $name");
        }
    }

    /**
     * Test whether or not the form has an input.
     *
     * @param string $name
     * @return bool
     */
    public function hasFormInput(string $name): bool
    {
        $results = $this->queryCssSelector("input[name=\"$name\"]");
        return $results->count() > 0;
    }

    /**
     * Dump all of the form values to aid debugging.
     *
     * @return array
     */
    public function getFormValues(): array
    {
        $nodes = $this->queryCssSelector("input,textarea");
        $r = [];
        foreach ($nodes as $node) {
            /** @var \DOMElement $node */
            $name = (string) $node->getAttribute("name");

            if ($node->tagName === "textarea") {
                $r[$name] = $node->nodeValue;
            } elseif ($node->getAttribute("type") == "checkbox") {
                $r[$name] = empty($node->getAttribute("checked")) ? null : (string) $node->getAttribute("value");
            } else {
                $r[$name] = (string) $node->getAttribute("value");
            }
        }

        return $r;
    }

    /**
     * Assert that there is a form textarea with a given name and value.
     *
     * @param string $name The form input name.
     * @param string|null $value The desired value. Pass `null` to not assert and just return the node.
     * @return \DOMNode Returns the matched node.
     */
    public function assertFormTextArea(string $name, ?string $value = null): \DOMNode
    {
        $node = $this->assertCssSelectorExists("textarea[name=\"$name\"]", "Could not find form input: $name");
        if ($value !== null) {
            $attr = $node->nodeValue;
            TestCase::assertSame($value, $attr);
        }

        return $node;
    }

    /**
     * Assert that there is a form dropdown with a given name and option value selected.
     *
     * @param string $name The form input name.
     * @param string|null $value The desired value. Pass `null` to not assert and just return the node.
     * @return \DOMElement Returns the matched node.
     */
    public function assertFormDropdown(string $name, ?string $value = null): \DOMElement
    {
        $node = $this->assertCssSelectorExists("select[name=\"$name\"]", "Could not find form dropdown: $name");
        if ($value !== null) {
            $attr = $node->getAttribute("data-value");
            TestCase::assertSame($value, $attr);
        }
        return $node;
    }

    /**
     * Assert that there is a form Token Multiselect with a given name and option value selected.
     *
     * @param string $name The form input name.
     * @param array|null $values The desired value. Pass `null` to not assert and just return the node.
     * @return \DOMElement Returns the matched node.
     */
    public function assertFormToken(string $name, ?array $values = null): \DOMElement
    {
        $resultNode = $this->queryCssSelector("div[data-react='tokensInputInLegacyForm']");
        $tokenInputFound = false;
        $tokenInputNode = null;
        $attributes = [];
        TestCase::assertNotNull($resultNode, "could not find any token input");
        for ($i = 0; $i < $resultNode->count(); $i++) {
            $tokenInputNode = $resultNode->item($i);
            $attributes = $tokenInputNode->getAttribute("data-props");
            if (empty($attributes)) {
                break;
            }
            $attributes = json_decode($attributes, true);
            if ($attributes["fieldName"] == $name) {
                $tokenInputFound = true;
                break;
            }
        }
        TestCase::assertTrue($tokenInputFound, "could not find token input: $name");
        if ($values !== null) {
            $attributes["initialValue"] = json_decode($attributes["initialValue"], true);
            foreach ($attributes["initialValue"] as $selectedValue) {
                $selectedValues[] = $selectedValue["value"];
            }

            TestCase::assertEquals($values, $selectedValues);
        }
        return $tokenInputNode;
    }

    /**
     * Assert that a css selector does not match.
     *
     * @param string $cssSelector The CSS selector query.
     * @param string|null $message
     *
     * @return void
     */
    public function assertCssSelectorNotExists(string $cssSelector, ?string $message = null): void
    {
        $resultNode = $this->queryCssSelector($cssSelector)->item(0);
        TestCase::assertNull($resultNode, $message ?? "Nothing matched the provided CSS selector");
    }

    /**
     * Assert that the text content at a resulting xpath is equivalent.
     *
     * @param string $xpath The xpath query.
     * @see https://devhints.io/xpath For a cheatsheet.
     * @param string $expected The expected text value.
     */
    public function assertXPathText(string $xpath, string $expected)
    {
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
    public function assertXPathExists(string $xpath): \DOMNode
    {
        $resultNode = $this->queryXPath($xpath)->item(0);
        TestCase::assertNotNull($resultNode, "Nothing matched the provided XPath");
        return $resultNode;
    }

    /**
     * Make sure the document doesn't have duplicate IDs.
     */
    public function assertNoDuplicateIDs(): void
    {
        $ids = [];
        $nodes = $this->queryXPath("//*[@id]");
        foreach ($nodes as $node) {
            /** @var \DOMElement $node */
            $id = $node->getAttribute("id");
            TestCase::assertArrayNotHasKey($id, $ids, "Duplicate ID found in HTML: $id");
            $ids[$id] = $id;
        }
    }
}
