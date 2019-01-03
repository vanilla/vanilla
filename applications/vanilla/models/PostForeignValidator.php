<?php
/**
 * @copyright 2009-2019 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

namespace Vanilla\Vanilla\Models;

use Gdn_Model;
use Gdn_Session as SessionInterface;
use Vanilla\Utility\Media\ForeignValidatorInterface;

/**
 * Class PostForeignValidator
 */
abstract class PostForeignValidator implements ForeignValidatorInterface {

    /** @var Gdn_Model */
    private $model;

    /** @var SessionInterface */
    private $session;

    /**
     * Setup.
     *
     * @param Gdn_Model $model
     * @param SessionInterface $session
     */
    public function __construct(Gdn_Model $model, SessionInterface $session) {
        $this->model = $model;
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function canAttach(string $foreignType, $foreignID): bool {
        $row = $this->model->getID($foreignID, DATASET_TYPE_ARRAY);
        if (!$row) {
            return false;
        }
        return ($row["InsertUserID"] === $this->session->UserID || $this->session->checkRankedPermission("Garden.Moderation.Manage"));
    }
}
