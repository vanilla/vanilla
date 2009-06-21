<?php

// User.
Gdn::FactoryInstall(Gdn::AliasUser, 'Gdn_UserModel', PATH_APPLICATIONS.DS.'garden'.DS.'models'.DS.'class.usermodel.php', Gdn::FactorySingleton, NULL, FALSE);
// Head.
Gdn::FactoryInstall('Head', 'Gdn_HeadModule', PATH_APPLICATIONS.DS.'garden'.DS.'modules'.DS.'class.headmodule.php', Gdn::FactorySingleton, NULL, FALSE);
// Menu.
Gdn::FactoryInstall('Menu', 'Gdn_MenuModule', PATH_APPLICATIONS.DS.'garden'.DS.'modules'.DS.'class.menumodule.php', Gdn::FactorySingleton, NULL, FALSE);
Gdn::Dispatcher()->PassProperty('Menu', Gdn::Factory('Menu'));
// Search.
Gdn::FactoryInstall('SearchModel', 'Gdn_SearchModel', PATH_APPLICATIONS.DS.'garden'.DS.'models'.DS.'class.searchmodel.php', Gdn::FactorySingleton, NULL, FALSE);

// Search Module.
Gdn::FactoryInstall('SearchModule', 'Gdn_SearchModule', PATH_APPLICATIONS.DS.'garden'.DS.'modules'.DS.'class.searchmodule.php', Gdn::FactorySingleton, NULL, FALSE);
Gdn::Dispatcher()->PassAsset('Search', Gdn::Factory('SearchModule'));