<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * This is a placeholder controller that allows plugins to have methods off of the root of the site.
 * If you want to take advantage of this then do the following:
 *  1. Create a method named <code>public function RootController_MyMethod_Create($Sender, $Args)</code>.
 *  2. Program your method just like any other created controller method.
 *  3. When you browse to <code>/mymethod</code> your method will be called.
 * @since 2.1
 */
class RootController extends Gdn_Controller {
   public function FetchViewLocation($View = '', $ControllerName = FALSE, $ApplicationFolder = FALSE, $ThrowError = TRUE) {
      if (!$ControllerName)
         $ControllerName = '';
      return parent::FetchViewLocation($View, $ControllerName, $ApplicationFolder, $ThrowError);
   }
}