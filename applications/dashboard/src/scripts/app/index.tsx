/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { addRoutes, addComponent } from "@dashboard/application";
import React from "react";
import { Route } from "react-router-dom";
import SignInPage from "./authenticate/SignInPage";
import PasswordPage from "./authenticate/PasswordPage";
import RecoverPasswordPage from "./authenticate/RecoverPasswordPage";
import Router from "@dashboard/components/Router";
import { stickyHeader } from "@dashboard/dom";

// These imports are all responsible for initializing themselves.
import "./user-content/emoji";
import "./user-content/spoilers";
import "./user-content/embeds/image";
import "./user-content/embeds/link";
import "./user-content/embeds/twitter";
import "./user-content/embeds/video";

addComponent("App", Router);

addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} />,
    <Route exact path="/authenticate/password" component={PasswordPage} />,
    <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} />,
]);

stickyHeader();
