import { addRoutes } from "@core/application";
import React from "react";
import { Route } from "react-router-dom";
import SignInPage from "./Authenticate/SignInPage";
import PasswordPage from "./Authenticate/PasswordPage";
import RecoverPasswordPage from "./Authenticate/RecoverPasswordPage";

addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} />,
    <Route exact path="/authenticate/password" component={PasswordPage} />,
    <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} />,
]);
