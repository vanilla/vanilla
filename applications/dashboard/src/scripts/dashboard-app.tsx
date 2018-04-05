import { addRoutes } from "@core/application";
import React from "react";
import { Route } from "react-router-dom";
import PasswordPage from "./Authenticate/PasswordPage";
import RequestPasswordPage from "./Authenticate/RequestPasswordPage";
import SignInPage from "./Authenticate/SignInPage";

// <Route exact path="/authenticate/signin" component={SignInPage} />,

addRoutes([
    <Route exact path="/authenticate/password" component={PasswordPage} />,
    <Route exact path="/authenticate/requestpassword" component={RequestPasswordPage} />,
]);

