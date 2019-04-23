<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Quill\Filterer;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\Formats\Bold;
use Vanilla\Formatting\Quill\Formats\Italic;
use Vanilla\Formatting\Quill\Formats\Link;

/**
 * General testing of Filterer.
 */
class FiltererTest extends TestCase {

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
