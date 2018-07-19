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

// These imports are all responsible for initializing themselves.
import "@dashboard/app/user-content/emoji";
import "@dashboard/app/user-content/spoilers";
import "@dashboard/app/user-content/embeds/image";
import "@dashboard/app/user-content/embeds/link";
import "@dashboard/app/user-content/embeds/twitter";
import "@dashboard/app/user-content/embeds/video";
import "@dashboard/app/user-content/embeds/instagram";
import "@dashboard/app/user-content/embeds/imgur";
import "@dashboard/app/user-content/embeds/soundcloud";
import "@dashboard/app/user-content/embeds/getty";
import "@dashboard/app/user-content/embeds/giphy";
import "@dashboard/app/user-content/embeds/codepen";
import { registerReducer } from "@dashboard/state/reducerRegistry";
import authenticateReducer from "@dashboard/state/authenticate/authenticateReducer";
import usersReducer from "@dashboard/state/users/usersReducer";

registerReducer("authenticate", authenticateReducer);
registerReducer("users", usersReducer);
addComponent("App", Router);
addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} />,
    <Route exact path="/authenticate/password" component={PasswordPage} />,
    <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} />,
]);

onReady(() => {
    stickyHeader();
});
