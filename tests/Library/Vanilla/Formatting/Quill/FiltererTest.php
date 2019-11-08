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
    public function setUp() {
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
     *
     * @dataProvider provideIO
     */
    public function testFilterer(string $input, string $output) {
        /** @var Filterer $filterer */
        $filterer = self::container()->get(Filterer::class);

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

        return [
            [
                "[$loadingEmbed, {\"insert\":\"\\n\"}]",
                "[{\"insert\":\"\\n\"}]"
            ],
            [
                "[{\"insert\":\"This is some Text\"}, $loadingEmbed, {\"insert\":\"loading embed should be gone\"}]",
                "[{\"insert\":\"This is some Text\"},{\"insert\":\"loading embed should be gone\"}]",
            ],
            [
                "[{\"insert\":\"Just a normal post\"}, {\"insert\":\"nothing special here\"}]",
                "[{\"insert\":\"Just a normal post\"},{\"insert\":\"nothing special here\"}]"
            ],
            [
                // This shouldn't be altered at all.
                $codeBlock,
                $codeBlock,
            ]
        ];
    }
}
