<?php

// User.
Gdn::FactoryInstall(Gdn::AliasUserModel, 'Gdn_UserModel', PATH_APPLICATIONS.DS.'garden'.DS.'models'.DS.'class.usermodel.php', Gdn::FactorySingleton);
// Permissions.
Gdn::FactoryInstall(Gdn::AliasPermissionModel, 'Gdn_PermissionModel', PATH_APPLICATIONS.DS.'garden'.DS.'models'.DS.'class.permissionmodel.php', Gdn::FactorySingleton);
// Roles.
Gdn::FactoryInstall('RoleModel', 'Gdn_RoleModel', PATH_APPLICATIONS.DS.'garden'.DS.'models'.DS.'class.rolemodel.php', Gdn::FactorySingleton);
// Head.
Gdn::FactoryInstall('Head', 'Gdn_HeadModule', PATH_APPLICATIONS.DS.'garden'.DS.'modules'.DS.'class.headmodule.php', Gdn::FactorySingleton);
// Menu.
Gdn::FactoryInstall('Menu', 'Gdn_MenuModule', PATH_APPLICATIONS.DS.'garden'.DS.'modules'.DS.'class.menumodule.php', Gdn::FactorySingleton);
Gdn::Dispatcher()->PassProperty('Menu', Gdn::Factory('Menu'));
// Search.
Gdn::FactoryInstall('SearchModel', 'Gdn_SearchModel', PATH_APPLICATIONS.DS.'garden'.DS.'models'.DS.'class.searchmodel.php', Gdn::FactorySingleton);