<?php
/**
 * @copyright 2009-2019 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

namespace Vanilla\Vanilla\Models;

use DiscussionModel;
use Gdn_Session as SessionInterface;

/**
 * Class DiscussionForeignValidator
 */
class DiscussionForeignValidator extends PostForeignValidator {

    /**
     * Setup.
     *
     * @param DiscussionModel $discussionModel
     * @param SessionInterface $session
     */
    public function __construct(DiscussionModel $discussionModel, SessionInterface $session) {
        parent::__construct($discussionModel, $session);
    }
}
