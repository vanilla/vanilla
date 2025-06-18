<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\FileEmbed;
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
     * Test the fix for https://higherlogic.atlassian.net/browse/VNLA-8498
     *
     * @return void
     */
    public function testNormalizeNestedUrl(): void
    {
        $initialUrl = "https://dev.vanilla.local/uploads/150/LKE0S2FWLFUP.zip";
        $data = [
            "url" => FileEmbed::makeDownloadUrl(FileEmbed::makeDownloadUrl($initialUrl)),
            "name" => "nested.zip",
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

        $data = $embed->getData();
        $this->assertEquals($initialUrl, $data["url"]);
        $this->assertEquals(FileEmbed::makeDownloadUrl($initialUrl), $data["downloadUrl"]);
    }
}
