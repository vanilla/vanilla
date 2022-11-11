<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla;

/**
 * Provide CSV test data.
 */
trait CsvProviderTrait
{
    /**
     * Provide CSV data.
     *
     * @return array
     */
    public static function provideCsvData(): array
    {
        $r = [
            [["firstColumn" => "test1", "secondColumn" => "test2"], "firstColumn,secondColumn\ntest1,test2\n"],
            [["firstColumn" => "", "secondColumn" => "test"], "firstColumn,secondColumn\n,test\n"],
            [["firstColumn" => null, "secondColumn" => "test"], "firstColumn,secondColumn\n,test\n"],
            [[0 => ["firstColumn" => "test1", "secondColumn" => "test2"]], "firstColumn,secondColumn\ntest1,test2\n"],
            [
                [
                    ["firstColumn" => "test1", "secondColumn" => "test2"],
                    ["firstColumn" => "test3", "secondColumn" => "test4"],
                ],
                "firstColumn,secondColumn\ntest1,test2\ntest3,test4\n",
            ],
            [
                [
                    "escapeCharTest" =>
                        "\"Double quotes\", 'single quote', comma and semicolon ; are special chars in a CSV;",
                ],
                "escapeCharTest\n\"\"\"Double quotes\"\", 'single quote', comma and semicolon ; are special chars in a CSV;\"\n",
            ],
            [
                ["firstColumn" => [0 => "test0", 1 => "test1", 2 => "test2"], "secondColumn" => "test3"],
                "firstColumn,secondColumn\n\"[\"\"test0\"\",\"\"test1\"\",\"\"test2\"\"]\",test3\n",
            ],
            [
                [["firstColumn" => "test1"], ["firstColumn" => "test2", "secondColumn" => "test2"]],
                "firstColumn,secondColumn\ntest1,\ntest2,test2\n",
            ],
            [
                [["firstColumn" => "test1", "secondColumn" => "test1"], ["firstColumn" => "test2"]],
                "firstColumn,secondColumn\ntest1,test1\ntest2,\n",
            ],
            [
                [
                    ["firstColumn" => "test1", "secondColumn" => "test2"],
                    ["firstColumn" => "test3", "thirdColumn" => "test4"],
                ],
                "firstColumn,secondColumn,thirdColumn\ntest1,test2,\ntest3,,test4\n",
            ],
            [
                [
                    ["firstColumn" => "test1", "secondColumn" => "test2"],
                    ["secondColumn" => "test4", "firstColumn" => "test3"],
                ],
                "firstColumn,secondColumn\ntest1,test2\ntest3,test4\n",
            ],
            [["secondColumn" => "test2", "firstColumn" => "test1"], "firstColumn,secondColumn\ntest1,test2\n"],
        ];
        return $r;
    }
}
