<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Garden\Container\Reference;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Quill\Filterer;
use VanillaTests\MinimalContainerTestCase;

/**
 * General testing of Filterer.
 */
class FiltererTest extends MinimalContainerTestCase {

    /**
     * Do some container registration.
     */
    public function setUp(): void {
        parent::setUp();
        self::container()->rule(EmbedService::class)
            ->addCall('registerEmbed', [QuoteEmbed::class, QuoteEmbed::TYPE])
            ->addCall('registerFilter', [new Reference(QuoteEmbedFilter::class)]);
    }


    /**
     * Assert that the filterer is validating json properly.
     *
     * @param string $input
     * @param string $output
     * @param bool $disableUrlEmbeds
     *
     * @dataProvider provideIO
     */
    public function testFilterer(string $input, string $output, bool $disableUrlEmbeds = false): void {
        /** @var Filterer $filterer */
        $filterer = self::container()->get(Filterer::class);
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Format.DisableUrlEmbeds', $disableUrlEmbeds, true, false);

        $filteredOutput = $filterer->filter($input);

        // Normalize inputted & outputter JSON
        $output = json_encode(json_decode($output));
        $filteredOutput = json_encode(json_decode($filteredOutput));
        $this->assertEquals($output, $filteredOutput);
    }

    /**
     * Provide input and expected output examples.
     *
     * @return array
     */
    public function provideIO() {
        $loadingEmbed = <<<JSON
   {
      "insert":{
         "embed-external":{
            "loaderData":{
               "type":"file",
               "file":{

               },
               "progressEventEmitter":{
                  "listeners":[
                     null
                  ]
               }
            },
            "dataPromise":{

            }
         }
      }
   }
JSON;

        $codeBlock = <<<JSON
[
    {
        "insert": ".TestClass {"
    },
    {
        "attributes": {
            "code-block": true
        },
        "insert": "\\n"
    },
    {
        "insert": "   height: 24px"
    },
    {
        "attributes": {
            "code-block": true
        },
        "insert": "\\n"
    },
    {
        "insert": "}"
    },
    {
        "attributes": {
            "code-block": true
        },
        "insert": "\\n"
    },
    {
        "insert": "\\n"
    }
]
JSON;

        $videoEmbed = <<<JSON
[
  {
    "insert": {
      "embed-external": {
        "data": {
          "height": 270,
          "width": 480,
          "photoUrl": "https:\/\/i.ytimg.com\/vi\/rIaz-l1Kf8w\/hqdefault.jpg",
          "videoID": "rIaz-l1Kf8w",
          "showRelated": false,
          "start": 0,
          "url": "https:\/\/www.youtube.com\/watch?v=rIaz-l1Kf8w",
          "embedType": "youtube",
          "name": "Scrum vs Kanban - What's the Difference?",
          "frameSrc": "https:\/\/www.youtube.com\/embed\/rIaz-l1Kf8w?feature=oembed&autoplay=1"
        },
        "loaderData": {
          "type": "link",
          "link": "https:\/\/www.youtube.com\/watch?v=rIaz-l1Kf8w"
        }
      }
    }
  }
]
JSON;

        $videoOutput = <<<JSON
[
  {
    "attributes": {
      "link": "https:\/\/www.youtube.com\/watch?v=rIaz-l1Kf8w"
    },
    "insert": "https:\/\/www.youtube.com\/watch?v=rIaz-l1Kf8w"
  }
]
JSON;


        return [
            [
                "[$loadingEmbed, {\"insert\":\"\\n\"}]",
                "[{\"insert\":\"\\n\"}]",
                false
            ],
            [
                "[{\"insert\":\"This is some Text\"}, $loadingEmbed, {\"insert\":\"loading embed should be gone\"}]",
                "[{\"insert\":\"This is some Text\"},{\"insert\":\"loading embed should be gone\"}]",
                false,
            ],
            [
                "[{\"insert\":\"Just a normal post\"}, {\"insert\":\"nothing special here\"}]",
                "[{\"insert\":\"Just a normal post\"},{\"insert\":\"nothing special here\"}]",
                false
            ],
            [
                // This shouldn't be altered at all.
                $codeBlock,
                $codeBlock,
                false,
            ],
            [
                $videoEmbed,
                $videoOutput,
                true
            ]
        ];
    }
}
