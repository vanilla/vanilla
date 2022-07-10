<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Pagination;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\Pagination\WebLinking;

/**
 * Tests for the weblinking class.
 */
class WebLinkingTest extends TestCase {

    /** @var WebLinking */
    private $webLinking;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
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


    /**
     * Test parsing of the link headers.
     *
     * @param string $header
     * @param array $expected
     *
     * @dataProvider provideParsing
     */
    public function testLinkParsing(string $header, array $expected) {
        $result = WebLinking::parseLinkHeaders($header);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array[]
     */
    public function provideParsing(): array {
        return [
            'just next' => [
                '<http://example.com/TheBook/pages?page=3>; rel="next"; title="next chapter"',
                [
                    'prev' => null,
                    'next' => 'http://example.com/TheBook/pages?page=3',
                ],
            ],
            'just prev' => [
                '<http://example.com/TheBook/chapter2>; rel="prev"; title="previous chapter"',
                [
                    'prev' => 'http://example.com/TheBook/chapter2',
                    'next' => null,
                ],
            ],
            'both' => [
                '<http://example.com/TheBook/pages?page=3>; rel="next"; title="next chapter",'.
                ' <http://example.com/TheBook/chapter2>; rel="prev"; title="previous chapter"',
                [
                    'prev' => 'http://example.com/TheBook/chapter2',
                    'next' => 'http://example.com/TheBook/pages?page=3',
                ],
            ],
            'invalid' => [
                'asdfasdf;kajsdfl;kjasdf',
                [
                    'prev' => null,
                    'next' => null,
                ]
            ]
        ];
    }
}
