<?php
/**
 * Vanilla model
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

use Vanilla\Exception\PermissionException;

/**
 * Introduces common methods that child classes can use.
 *
 * @deprecated
 */
abstract class VanillaModel extends Gdn_Model {

    use \Vanilla\FloodControlTrait;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $Name Database table name.
     */
    public function __construct($Name = '') {
        parent::__construct($Name);
    }

    /**
     * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
     *
     * Users cannot post more than $SpamCount comments within $SpamTime
     * seconds or their account will be locked for $SpamLock seconds.
     *
     * @deprecated
     *
     * @param string $type Valid values are 'Comment' or 'Discussion'.
     * @return bool Whether spam check is positive (TRUE = spammer).
     */
    public function checkForSpam($type) {
        deprecated(__CLASS__.' '.__METHOD__, 'FloodControlTrait::isUserSpamming()');

        $session = Gdn::session();

        // Validate $Type
        if (!in_array($type, array('Comment', 'Discussion'))) {
            trigger_error(ErrorMessage(sprintf('Spam check type unknown: %s', $type), 'VanillaModel', 'CheckForSpam'), E_USER_ERROR);
        }

        $storageObject = FloodControlHelper::configure($this, $type);
        $isUserSpamming = $this->isUserSpamming($session->User->UserID, $storageObject);

        return $isUserSpamming;
    }

    /**
     * Verify the current user has a permission in a category.
     *
     * @param string|array $permission The permission slug(s) to check (e.g. Vanilla.Discussions.View).
     * @param int $categoryID The category's numeric ID.
     * @throws PermissionException if the current user does not have the permission in the category.
     */
    public function categoryPermission($permission, $categoryID) {
        $category = CategoryModel::categories($categoryID);
        if ($category) {
            $id = $category['PermissionCategoryID'];
        } else {
            $id = -1;
        }
        $permissions = (array)$permission;

        if (!Gdn::session()->getPermissions()->hasAny($permissions, $id)) {
            throw new PermissionException($permissions);
        }
    }
}
