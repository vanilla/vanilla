<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\FileEmbedFilter;
use VanillaTests\MinimalContainerTestCase;

/**
 * Validation logic test.
 */
class FileEmbedTest extends MinimalContainerTestCase
{
    /**
     * Ensure we can create file embed embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat()
    {
        $oldDataJSON = <<<JSON
 {
    "url": "https://dev.vanilla.local/uploads/150/LKE0S2FWLFUP.zip",
    "type": "file",
    "attributes": {
        "mediaID": 62,
        "name": "___img_onload_prompt(1)_ (2).zip",
        "path": "150/LKE0S2FWLFUP.zip",
        "type": "application/zip",
        "size": 41,
        "active": 1,
        "insertUserID": 4,
        "dateInserted": "2019-06-14 14:09:38",
        "foreignID": 4,
        "foreignTable": "embed",
        "imageWidth": null,
        "imageHeight": null,
        "thumbWidth": null,
        "thumbHeight": null,
        "thumbPath": null,
        "foreignType": "embed",
        "url": "https://dev.vanilla.local/uploads/150/LKE0S2FWLFUP.zip"
    }
 }
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = new FileEmbed($oldData);
        $this->assertInstanceOf(FileEmbed::class, $dataEmbed);
    }

    /**
     * Make sure that file URLs that are not coming from our CDN are removed.
     *
     * @return void
     */
    public function testSanitizingURL(): void
    {
        $data = [
            "url" => "https://evil-corp/f-society.zip",
            "name" => "f-society.zip",
            "foreignUrl" => "https://evil-corp/f-society.zip",
            "type" => "file",
            "size" => 0,
            "dateInserted" => "2015-05-09 00:00:01",
            "mediaID" => 62,
            "foreignID" => 4,
            "foreignTable" => "embed",
            "active" => 1,
            "insertUserID" => 4,
            "imageWidth" => null,
            "imageHeight" => null,
            "thumbWidth" => null,
            "thumbHeight" => null,
            "thumbPath" => null,
            "foreignType" => "embed",
        ];

        $embed = new FileEmbed($data);
        $body = $embed->renderHtml();
        $this->assertEquals("<div></div>", $body);
    }

    /**
     * Tests that the FileEmbedFilter replaces the URL with the media API endpoint if a mediaID exists.
     *
     * @return void
     *
     */
    public function testFileEmbedFilter()
    {
        $mediaID = 22;
        $data = [
            "url" => "https://dev.vanilla.local/uploads/R5ANYXKSFK1I/dummy.pdf",
            "name" => "dummy.pdf",
            "type" => "file",
            "size" => 13264,
            "displaySize" => "large",
            "float" => "none",
            "mediaID" => $mediaID,
            "dateInserted" => "2024-12-02T22:02:10+00:00",
            "insertUserID" => 2,
            "foreignType" => "embed",
            "foreignID" => 2,
        ];
        $embed = new FileEmbed($data);
        $filter = new FileEmbedFilter();

        $embed = $filter->filterEmbed($embed);
        $data = $embed->getData();
        $this->assertArrayHasKey("url", $data);
        $this->assertEquals("http://vanilla.test/minimal-container-test/api/v2/media/$mediaID/download", $data["url"]);
    }
}
