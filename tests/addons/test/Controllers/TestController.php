<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Some test methods.
 */
class TestController extends \Gdn_Controller
{
    /**
     * A dummy endpoint that needs sign in permission.
     */
    public function noGuest(): void
    {
        $this->permission("Garden.SignIn.Allow");

        $this->render("blank", "utility", "dashboard");
    }

    /**
     * This endpoint needs settings.manage.
     */
    public function admin(): void
    {
        $this->permission("Garden.Settings.Manage");
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Throw an exception.
     */
    public function permissionException(): void
    {
        throw permissionException("Garden.Settings.Manage");
    }
}
