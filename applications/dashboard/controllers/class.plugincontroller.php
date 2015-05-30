<?php if (!defined('APPLICATION')) exit();

/**
 * The Plugin controller offers plugins a place to have their own pages. Create
 * a custom plugin page by using Pluggable's Create method to magically add a
 * method to this controller.
 *
 * For example, to create a page at http://localhost/garden/plugin/mynewmethod
 * your plugin should have a method called:
 *  public function PluginController_MyNewMethod_Create($Sender) {
 *     $Sender->Render('/path/to/some/view.php');
 *  }
 *
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */
class PluginController extends DashboardController {
}
