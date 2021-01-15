<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Utils;

use VanillaTests\APIv0\TestDispatcher;

/**
 * Trait TestModerationControllerTrait
 *
 * @method TestDispatcher bessy()
 */
trait TestModerationControllerTrait {

    /**
     * Post to moderation/confirmdiscussionmoves and return the new category discussions list
     *
     * @param ?int $discussionID
     * @param ?int $category
     * @param array $options
     * @return ?array
     */
    public function moveDiscussion($discussionID = null, $category = null, $options = []): ?array {
        if (empty($category)) {
            return null;
        }

        $endpoint ='/moderation/confirmdiscussionmoves';
        if ($discussionID) {
            $endpoint .= '?discussionid='.$discussionID;
        }

        $options += ['CategoryID' => $category['CategoryID']];
        $this->bessy()->post($endpoint, $options);
        return $this->bessy()->get('/categories/'.$category['UrlCode'])->data('Discussions')->resultArray();
    }
}
