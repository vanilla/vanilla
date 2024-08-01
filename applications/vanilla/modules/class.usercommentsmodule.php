<?php
/**
 * User comments module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.3
 */

/**
 * Renders recently posted comments from a specific user
 */
class UserCommentsModule extends Gdn_Module
{
    /** @var int Display limit. */
    public $limit = 10;

    /** @var int The ID of the user for whom you are showing the comments. */
    public $userID = null;

    /**
     * Construct the module. This module is designed to run in the profile controller. If it is not in the
     * profile controller and no UserID was declared when it was instantiated do not populate $this->userID.
     *
     * @param GDN_Controller $sender
     * @throws Exception
     */
    public function __construct($sender)
    {
        parent::__construct();
        $this->_ApplicationFolder = "vanilla";
        $this->fireEvent("Init");
        //If you are being executed from the profile controller, get the UserID.
        if (strtolower(val("ControllerName", $sender)) === "profilecontroller") {
            $this->userID = valr("User.UserID", $sender);
        }
    }

    /**
     * Get the data for the module.
     *
     * @param int|bool $limit Override the number of comments to display.
     */
    public function getData($limit = false)
    {
        if (!$limit) {
            $limit = $this->limit;
        }

        if (!$this->userID) {
            return;
        }

        $userModel = new UserModel();
        $this->setData("User", $userModel->getID($this->userID));
        $commentsModel = new CommentModel();
        $this->setData("Comments", $commentsModel->getByUser2($this->userID, $limit, 0));
    }

    /**
     * Set where the module will be shown.
     *
     * @return string
     */
    public function assetTarget()
    {
        return "Panel";
    }

    /**
     * Output the module as a string.
     *
     * @return string|void
     */
    public function toString()
    {
        // If there is no userID show nothing.
        if (!$this->userID) {
            return;
        }

        if (!$this->data("Comments")) {
            $this->getData();
        }

        return parent::toString();
    }
}
