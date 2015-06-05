<?php if (!defined('APPLICATION')) exit();

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
