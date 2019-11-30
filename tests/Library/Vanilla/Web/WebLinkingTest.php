<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Web\WebLinking;

class WebLinkingTest extends SharedBootstrapTestCase {

    /** @var WebLinking */
    private $webLinking;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
        $this->webLinking = new WebLinking();
    }

    /**
     * Test: Add a basic link.
     */
    public function testAddLink() {
        $this->webLinking->addLink('next', '/nextPage');

        $this->assertEquals('Link: </nextPage>; rel="next"', $this->webLinking->getLinkHeader());
    }

    /**
     * Test: Add multiple basic link.
     */
    public function testAddLinks() {
        $this->webLinking->addLink('next', '/nextPage');
        $this->webLinking->addLink('prev', '/prevPage');

        $this->assertEquals('Link: </nextPage>; rel="next", </prevPage>; rel="prev"', $this->webLinking->getLinkHeader());
    }

    /**
     * Test: Add link with attributes.
     */
    public function testAddLinkWExtraAttributes() {
        $this->webLinking->addLink('prev', 'http://example.com/TheBook/chapter2', ['title' => 'previous chapter']);

        $this->assertEquals(
            'Link: <http://example.com/TheBook/chapter2>; rel="prev"; title="previous chapter"',
            $this->webLinking->getLinkHeader()
        );
    }

    /**
     * Test: Add an URI as a relation.
     */
    public function testAddURIRel() {
        $this->webLinking->addLink('http://example.net/foo', '/');

        $this->assertEquals(
            'Link: </>; rel="http://example.net/foo"',
            $this->webLinking->getLinkHeader()
        );
    }

    /**
     * Test: Remove a link.
     */
    public function testRemoveLink() {
        $this->webLinking->addLink('next', '/nextPage');
        $this->webLinking->addLink('prev', '/prevPage');
        $this->webLinking->removeLink('prev', '/prevPage');

        $this->assertEquals(
            'Link: </nextPage>; rel="next"',
            $this->webLinking->getLinkHeader()
        );
    }

    /**
     * Test: Remove all link specified by a relation.
     */
    public function testRemoveLinksByRel() {
        $this->webLinking->addLink('next', '/nextPage');
        $this->webLinking->addLink('next', '/nextPageToo');
        $this->webLinking->removeLink('next');

        $this->assertEmpty($this->webLinking->getLinkHeader());
    }

    /**
     * Test: Clear all links from the object.
     */
    public function testClearLinks() {
        $this->webLinking->addLink('next', '/nextPage');
        $this->webLinking->addLink('next', '/nextPageToo');
        $this->webLinking->clear();

        $this->assertEmpty($this->webLinking->getLinkHeader());
    }
}
