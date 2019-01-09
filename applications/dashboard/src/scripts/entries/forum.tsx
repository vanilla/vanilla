/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { addRoutes, addComponent, onReady } from "@library/application";
import React from "react";
import { Route } from "react-router-dom";
import Router from "@dashboard/components/Router";
import { stickyHeader } from "@library/dom";
import { registerReducer } from "@library/state/reducerRegistry";
// The forum section needs these legacy scripts that have been moved into the bundled JS so it could be refactored.
// Other sections should not need this yet.
import "@dashboard/legacy";
import { initAllUserContent } from "@library/user-content";
import authenticateReducer from "@dashboard/pages/authenticate/authenticateReducer";
import SignInPage from "@dashboard/pages/authenticate/SignInPage";
import PasswordPage from "@dashboard/pages/authenticate/PasswordPage";
import RecoverPasswordPage from "@dashboard/pages/recoverPassword/RecoverPasswordPage";
import UsersModel from "@library/users/UsersModel";
import NotificationsModel from "@library/notifications/NotificationsModel";

initAllUserContent();

// Redux
registerReducer("authenticate", authenticateReducer);
registerReducer("users", new UsersModel().reducer);
registerReducer("notifications", new NotificationsModel().reducer);

// Routing
addComponent("App", Router);
addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} />,
    <Route exact path="/authenticate/password" component={PasswordPage} />,
    <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} />,
]);

// Other site initializations
onReady(() => {
    stickyHeader();
});
