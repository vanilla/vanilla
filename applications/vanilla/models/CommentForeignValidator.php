<?php
/**
 * @copyright 2009-2019 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

namespace Vanilla\Vanilla\Models;

use CommentModel;
use Gdn_Session as SessionInterface;

/**
 * Class CommentForeignValidator
 */
class CommentForeignValidator extends PostForeignValidator {

    /**
     * Setup.
     *
     * @param CommentModel $commentModel
     * @param SessionInterface $session
     */
    public function __construct(CommentModel $commentModel, SessionInterface $session) {
        parent::__construct($commentModel, $session);
    }
}
