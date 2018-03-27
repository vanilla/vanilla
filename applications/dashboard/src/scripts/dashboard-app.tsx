import React from "react";
import { addRoutes } from "@core/application";
import { Route } from "react-router-dom";
import SignInPage from "./Authenticate/SignInPage";
import PasswordPage from "./Authenticate/PasswordPage";

addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} />,
    <Route exact path="/authenticate/password" component={PasswordPage} />,
]);
