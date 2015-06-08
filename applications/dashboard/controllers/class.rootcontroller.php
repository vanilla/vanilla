<?php
/**
 * This is a placeholder controller that allows plugins to have methods off of the root of the site.
 *
 * If you want to take advantage of this then do the following:
 *  1. Create a method named <code>public function rootController_MyMethod_Create($Sender, $Args)</code>.
 *  2. Program your method just like any other created controller method.
 *  3. When you browse to <code>/mymethod</code> your method will be called.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Handle endpoints for virtual controllers.
 */
class RootController extends Gdn_Controller {

    /**
     * Get the file location of a view.
     *
     * @param string $View
     * @param bool $ControllerName
     * @param bool $ApplicationFolder
     * @param bool $ThrowError
     * @return bool|mixed
     * @throws Exception
     */
    public function fetchViewLocation($View = '', $ControllerName = false, $ApplicationFolder = false, $ThrowError = true) {
        if (!$ControllerName) {
            $ControllerName = '';
        }
        return parent::fetchViewLocation($View, $ControllerName, $ApplicationFolder, $ThrowError);
    }
}
