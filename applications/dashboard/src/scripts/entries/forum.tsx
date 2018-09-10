/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { addRoutes, addComponent, onReady } from "@library/application";
import React from "react";
import { Route } from "react-router-dom";
import Router from "@dashboard/components/Router";
import { stickyHeader } from "@library/dom";
import { registerReducer } from "@library/state/reducerRegistry";

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

// The forum section needs these legacy scripts that have been moved into the bundled JS so it could be refactored.
// Other sections should not need this yet.
import "@dashboard/legacy";
import { initAllUserContent } from "@library/user-content";
import authenticateReducer from "@dashboard/pages/authenticate/authenticateReducer";
import usersReducer from "@dashboard/usersReducer";
import SignInPage from "@dashboard/pages/authenticate/SignInPage";
import PasswordPage from "@dashboard/pages/authenticate/PasswordPage";
import RecoverPasswordPage from "@dashboard/pages/recoverPassword/RecoverPasswordPage";
