<?php
/**
 * The Plugin controller offers plugins a place to have their own pages.
 *
 * Create a custom plugin page by using Pluggable's Create method to magically add a method to this controller.
 * For example, to create a page at http://localhost/garden/plugin/mynewmethod
 * your plugin should have a method called:
 *  public function pluginController_MyNewMethod_Create($Sender) {
 *     $Sender->render('/path/to/some/view.php');
 *  }
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /plugin endpoint.
 */
class PluginController extends DashboardController
{
}
