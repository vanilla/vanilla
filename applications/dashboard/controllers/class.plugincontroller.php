<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

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
 */
class PluginController extends DashboardController {
}