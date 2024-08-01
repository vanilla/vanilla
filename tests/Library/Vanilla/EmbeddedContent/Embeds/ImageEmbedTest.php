<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\GiphyEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Validation logic test.
 */
class ImageEmbedTest extends MinimalContainerTestCase
{
    /**
     * Ensure we can create giphy embed from the old data format that might still
     * live in the DB.
     */
    public function testPartialDataDefaults()
    {
        $oldDataJSON = <<<JSON
{
    "url":"http:\/\/dev.vanilla.localhost\/uploads\/HOM6P5MDM3BH\/img-2980.jpg",
    "embedType":"image"
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = new ImageEmbed($oldData);
        $this->assertInstanceOf(ImageEmbed::class, $dataEmbed);
        $this->assertEquals(
            [
                "url" => "http://dev.vanilla.localhost/uploads/HOM6P5MDM3BH/img-2980.jpg",
                "name" => "img-2980.jpg",
                "type" => "unknown",
                "size" => 0,
                "width" => 1280,
                "height" => 720,
                "displaySize" => "large",
                "float" => "none",
                "embedType" => "image",
            ],
            $dataEmbed->jsonSerialize()
        );
    }
}
