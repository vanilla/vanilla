/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onContent } from "@library/utility/appUtils";
import { Route } from "react-router-dom";
import { registerReducer } from "@library/redux/reducerRegistry";
// The forum section needs these legacy scripts that have been moved into the bundled JS so it could be refactored.
// Other sections should not need this yet.
import "@dashboard/legacy";
import { convertAllUserContent, initAllUserContent } from "@library/content";
import authenticateReducer from "@dashboard/pages/authenticate/authenticateReducer";
import SignInPage from "@dashboard/pages/authenticate/SignInPage";
import PasswordPage from "@dashboard/pages/authenticate/PasswordPage";
import RecoverPasswordPage from "@dashboard/pages/recoverPassword/RecoverPasswordPage";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import { Router } from "@library/Router";
import { AppContext } from "@library/AppContext";
import { addComponent } from "@library/utility/componentRegistry";

initAllUserContent();
onContent(convertAllUserContent);

// Redux
registerReducer("authenticate", authenticateReducer);
registerReducer("notifications", new NotificationsModel().reducer);

Router.addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} key="signin" />,
    <Route exact path="/authenticate/password" component={PasswordPage} key="password" />,
    <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} key="recover" />,
]);

// Routing
addComponent("App", () => (
    <AppContext variablesOnly>
        <Router disableDynamicRouting />
    </AppContext>
));
