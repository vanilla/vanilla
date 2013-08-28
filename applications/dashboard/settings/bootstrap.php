<?php if (!defined('APPLICATION')) exit();

/**
 * Dashboard Application Bootstrap
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

// User.
Gdn::FactoryInstall(Gdn::AliasUserModel, 'UserModel');

// Permissions.
Gdn::FactoryInstall(Gdn::AliasPermissionModel, 'PermissionModel');

// Roles.
Gdn::FactoryInstall('RoleModel', 'RoleModel');

// Head.
Gdn::FactoryInstall('Head', 'HeadModule');

// Menu.
Gdn::FactoryInstall('Menu', 'MenuModule');
Gdn::Dispatcher()->PassProperty('Menu', Gdn::Factory('Menu'));
