<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Metadata;

use PHPUnit\Framework\TestCase;
use Vanilla\Metadata\Parser\RSSFeedParser;

/**
 * Tests RSS Feed Parsing.
 *
 * @package VanillaTests\Library\Vanilla\Metadata
 */
class RSSFeedParserTest extends TestCase
{
    /**
     * @return string[]
     */
    public function dataXML(): array
    {
        return [
            [
                '<rss xmlns:media="https://vanillaforums.com/rss/" version="2.0">
                <channel>
                    <link>https://vanillaforums.com/channel</link>
                    <title>Channel</title>
                    <description>Channel description</description>
                    <image>
                        <link>https://vanillaforums.com/channel/images</link>
                        <title>An image</title>
                        <url>https://vanillaforums.com/channel.png</url>
                    </image>
                    <item>
                        <link>https://vanillaforums.com/title-1</link>
                        <pubDate>Fri, 19 Feb 2021 17:50:40 GMT</pubDate>
                        <title>Title 1</title>
                        <description>
                        <![CDATA[ <img src="/imäges.JPG" alt="An image" title="Image Title" /><p>Description.</p> ]]>
                        </description>
                    </item>
                    <item>
                        <link>https://vanillaforums.com/title-3</link>
                        <pubDate>Thu, 18 Feb 2021 18:35:40 GMT</pubDate>
                        <title><![CDATA[ Title 3]]></title>
                        <description>Description 3</description>
                        <category>Cat 1</category>
                        <enclosure url="https://vanillaforums.com/media/video.mp4" length="123456" type="video/mp4" />
                    </item>
                    <item>
                        <link>https://vanillaforums.com/title-4</link>
                        <pubDate>Sun, 18 Dec 2022 18:35:40 GMT</pubDate>
                        <title>No Description Item</title>
                        <description/>
                        <category>Cat 1</category>
                        <enclosure url="https://vanillaforums.com/media/video.mp4" length="123456" type="video/mp4" />
                    </item>
                </channel>
            </rss>',
            ],
        ];
    }

    /**
     * Test RSS feed parsing.
     *
     * @dataProvider dataXML
     *
     * @param string $xmlContent
     */
    public function testGetResults(string $xmlContent): void
    {
        $xmlDOM = new \DOMDocument();
        $xmlDOM->loadXML($xmlContent);
        $rssParser = new RSSFeedParser();
        $results = $rssParser->parse($xmlDOM);
        $this->assertCount(3, $results["item"]);
        $this->assertNotEmpty($results["channel"]);
        $this->assertArrayHasKey("image", $results["channel"]);
        $this->assertEquals("https://vanillaforums.com/channel.png", $results["channel"]["image"]["url"]);
        $this->assertArrayHasKey("pubDate", $results["item"][0]);
        $this->assertArrayHasKey("img", $results["item"][0]);
        $this->assertArrayNotHasKey("img", $results["item"][1]);
        $this->assertEquals("/imäges.JPG", $results["item"][0]["img"]["src"]);
        $this->assertEquals("Title 3", $results["item"][1]["title"]);
        $this->assertEquals("Cat 1", $results["item"][1]["category"]);

        $this->assertEquals("No Description Item", $results["item"][2]["title"]);
        $this->assertEquals(null, $results["item"][2]["description"]);

        $description = '<img src="/imäges.JPG" alt="An image" title="Image Title" /><p>Description.</p>';
        $this->assertEquals($description, $results["item"][0]["description"]);
        $this->assertEquals("https://vanillaforums.com/media/video.mp4", $results["item"][1]["enclosure"]["url"]);
    }
}
