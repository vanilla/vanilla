<?php if (!defined('APPLICATION')) exit();

// User.
Gdn::FactoryInstall(Gdn::AliasUserModel, 'UserModel', PATH_APPLICATIONS.DS.'dashboard'.DS.'models'.DS.'class.usermodel.php', Gdn::FactorySingleton);
// Permissions.
Gdn::FactoryInstall(Gdn::AliasPermissionModel, 'PermissionModel', PATH_APPLICATIONS.DS.'dashboard'.DS.'models'.DS.'class.permissionmodel.php', Gdn::FactorySingleton);
// Roles.
Gdn::FactoryInstall('RoleModel', 'RoleModel', PATH_APPLICATIONS.DS.'dashboard'.DS.'models'.DS.'class.rolemodel.php', Gdn::FactorySingleton);
// Head.
Gdn::FactoryInstall('Head', 'HeadModule', PATH_APPLICATIONS.DS.'dashboard'.DS.'modules'.DS.'class.headmodule.php', Gdn::FactorySingleton);
// Menu.
Gdn::FactoryInstall('Menu', 'MenuModule', PATH_APPLICATIONS.DS.'dashboard'.DS.'modules'.DS.'class.menumodule.php', Gdn::FactorySingleton);
Gdn::Dispatcher()->PassProperty('Menu', Gdn::Factory('Menu'));
// Search.
Gdn::FactoryInstall('SearchModel', 'SearchModel', PATH_APPLICATIONS.DS.'dashboard'.DS.'models'.DS.'class.searchmodel.php', Gdn::FactorySingleton);