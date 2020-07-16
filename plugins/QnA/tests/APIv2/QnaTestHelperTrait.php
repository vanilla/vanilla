<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

/**
 * Trait QnaTestHelperTrait
 */
trait QnaTestHelperTrait {
    /**
     * Assert an array has all necessary question fields.
     *
     * @param array $discussion
     * @param array $expectedAttributes
     */
    protected function assertIsQuestion($discussion, $expectedAttributes = []) {
        $this->assertIsArray($discussion);

        $this->assertArrayHasKey('type', $discussion);
        $this->assertEquals('question', $discussion['type']);

        $this->assertArrayHasKey('attributes', $discussion);
        $this->assertArrayHasKey('question', $discussion['attributes']);

        $this->assertArrayHasKey('status', $discussion['attributes']['question']);
        $this->assertArrayHasKey('dateAccepted', $discussion['attributes']['question']);
        $this->assertArrayHasKey('dateAnswered', $discussion['attributes']['question']);

        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals($value, $discussion['attributes']['question'][$attribute]);
        }
    }

    /**
     * Assert an array has all necessary answer fields.
     *
     * @param array $comment
     * @param array $expectedAttributes
     */
    protected function assertIsAnswer($comment, $expectedAttributes = []) {
        $this->assertIsArray($comment);

        $this->assertArrayHasKey('attributes', $comment);
        $this->assertArrayHasKey('answer', $comment['attributes']);

        $this->assertArrayHasKey('status', $comment['attributes']['answer']);
        $this->assertArrayHasKey('dateAccepted', $comment['attributes']['answer']);
        $this->assertArrayHasKey('acceptUserID', $comment['attributes']['answer']);

        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals($value, $comment['attributes']['answer'][$attribute]);
        }
    }
}
