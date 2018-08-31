/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { addRoutes, addComponent, onReady } from "@dashboard/application";
import React from "react";
import { Route } from "react-router-dom";
import SignInPage from "@dashboard/app/authenticate/SignInPage";
import PasswordPage from "@dashboard/app/authenticate/PasswordPage";
import RecoverPasswordPage from "@dashboard/app/authenticate/RecoverPasswordPage";
import Router from "@dashboard/components/Router";
import { stickyHeader } from "@dashboard/dom";
import { registerReducer } from "@dashboard/state/reducerRegistry";
import authenticateReducer from "@dashboard/state/authenticate/authenticateReducer";
import usersReducer from "@dashboard/state/users/usersReducer";
import { initAllUserContent } from "@dashboard/app/user-content";

initAllUserContent();

// Redux
registerReducer("authenticate", authenticateReducer);
registerReducer("users", usersReducer);

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
