<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Quill\Filterer;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\Formats\Bold;
use Vanilla\Formatting\Quill\Formats\Italic;
use Vanilla\Formatting\Quill\Formats\Link;

/**
 * General testing of Filterer.
 */
class FiltererTestMinimal extends MinimalContainerTestCase {

    /**
     * Assert that the filterer is validating json properly.
     *
     * @param string $input
     * @param string $output
     *
     * @dataProvider provideIO
     */
    public function testFilterer(string $input, string $output) {
        $filterer = new Filterer();

        $filteredOutput = $filterer->filter($input);

        // Normalize inputted & outputter JSON
        $output = json_encode(json_decode($output));
        $filteredOutput = json_encode(json_decode($filteredOutput));
        $this->assertEquals($output, $filteredOutput);
    }

    /**
     * Test that
     * - unneeded embed data gets stripped off.
     * - XSS in the body is prevent. We always have a fully rendered body.
     */
    public function testFilterEmbedData() {
        $filterer = new Filterer();
        $replacedUrl = 'http://test.com/replaced';

        $input = [
            [
                'insert' => [
                    'embed-external' => [
                        'data' => [
                            'type' => QuoteEmbed::TYPE,
                            'body' => "Fake body contents, should be replaced.",
                            'bodyRaw' => 'Rendered Body',
                            'format' => 'Markdown',
                        ],
                    ],
                ],
            ],
            [
                'insert' => [
                    'embed-external' => [
                        'data' => [
                            'type' => QuoteEmbed::TYPE,
                            'format' => RichFormat::FORMAT_KEY,
                            'body' => '<div><script>alert("This should be replaced!")</script></div>',
                            'bodyRaw' => [
                                [
                                    'insert' => [
                                        'embed-external' => [
                                            'data' => [
                                                'url' => $replacedUrl,
                                            ],
                                        ],
                                    ],
                                ],
                                [ 'insert' => 'After Embed\n' ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Contents replaced with a link.
        $expectedEmbedBodyRaw = [
            [
                'insert' => $replacedUrl,
                'attributes' => [
                    'link' => $replacedUrl,
                ],
            ],
            [ 'insert' => "\n" ],
            [ 'insert' => 'After Embed\n' ],
        ];

        $expected = [
            [
                'insert' => [
                    'embed-external' => [
                        'data' => [
                            'type' => QuoteEmbed::TYPE,
                            'body' => "<p>Rendered Body</p>\n",
                            'bodyRaw' => 'Rendered Body',
                            'format' => 'Markdown',
                        ],
                    ],
                ],
            ],
            [
                'insert' => [
                    'embed-external' => [
                        'data' => [
                            'type' => QuoteEmbed::TYPE,
                            'format' => 'Rich',
                            'body' => \Gdn::formatService()->renderQuote($expectedEmbedBodyRaw, RichFormat::FORMAT_KEY),
                            'bodyRaw' => $expectedEmbedBodyRaw,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(json_encode($expected), $filterer->filter(json_encode($input)));
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
