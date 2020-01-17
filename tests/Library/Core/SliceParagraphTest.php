<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for sliceParagraphTest().
 */

class SliceParagraphTest extends TestCase {

    /**
     * Test {@link sliceParagraph()} against several scenarios.
     *
     * @param string $testString The string to slice.
     * @param int|array $testLimits either int ($maxLength) or array ($maxLength, $minLength).
     * @param string $testSuffix The suffix to slice on if the string must be sliced mid-sentence.
     * @param string $expected The expected result.
     * @dataProvider provideTestSliceParagraphArrays
     */
    public function testSliceParagraph($testString, $testLimits, $testSuffix, $expected) {
        $actual = sliceParagraph($testString, $testLimits, $testSuffix);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for {@link sliceParagraph()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestSliceParagraphArrays() {
        $r = [
            'sliceOnParagraph' => [
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life : wherever you turn, you 
                stumble at once upon the incorrigible mob of humanity, 
                swarming in all directions, crowding and soiling everything, 
                like flies in summer.",
                [500, 30],
                '-',
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n",
            ],
            'sliceOnParagraphLimitsInt' => [
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life : wherever you turn, you 
                stumble at once upon the incorrigible mob of humanity, 
                swarming in all directions, crowding and soiling everything, 
                like flies in summer.",
                500,
                '-',
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n",
            ],
            'maxLengthGreaterThanStringLength' => [
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life : wherever you turn, you 
                stumble at once upon the incorrigible mob of humanity, 
                swarming in all directions, crowding and soiling everything, 
                like flies in summer.",
                2000,
                '-',
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life : wherever you turn, you 
                stumble at once upon the incorrigible mob of humanity, 
                swarming in all directions, crowding and soiling everything, 
                like flies in summer.",
            ],
            'splitOnSentence' => [
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life : wherever you turn, you 
                stumble at once upon the incorrigible mob of humanity, 
                swarming in all directions, crowding and soiling everything, 
                like flies in summer.",
                40,
                '-',
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive.",
            ],
            'splitMidSentence' => [
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life : wherever you turn, you 
                stumble at once upon the incorrigible mob of humanity, 
                swarming in all directions, crowding and soiling everything, 
                like flies in summer.",
                [501, 500],
                ',',
                "Herodotus relates that Xerxes wept at the sight 
                of his army, which stretched further than the eye 
                could reach, in the thought that of all these, after a 
                hundred years, not one would be alive. And in look-
                ing over a huge catalogue of new books, one might 
                weep at thinking that, when ten years have passed, 
                not one of them will be heard of.\n\nIt is in literature as in life :",
            ],
            'noSentence' => [
                "This sentence is not a sentence because it has no punctuation",
                25,
                ' ',
                "This sentence is not a ",
            ],
            'noSpaceToSplitOn' => [
                "This sentence is not a sentence because it has no punctuation",
                2,
                ' ',
                "This sentence is not a sentence because it has no punctuation ",
            ],
        ];

        return $r;
    }
}
