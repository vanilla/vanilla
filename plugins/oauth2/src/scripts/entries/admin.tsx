/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Router } from "@library/Router";
import { allAuthenticatorRoutes } from "@oauth2/AuthenticatorRoutes";
import { addComponent } from "@library/utility/componentRegistry";
import { authenticatorsSlice } from "@oauth2/AuthenticatorReducer";
import { registerReducer } from "@library/redux/reducerRegistry";

Router.addRoutes(allAuthenticatorRoutes);

registerReducer(authenticatorsSlice.name, authenticatorsSlice.reducer);

addComponent("oauth2App", () => {
    return <Router sectionRoots={["/oauth2-settings"]} />;
});
