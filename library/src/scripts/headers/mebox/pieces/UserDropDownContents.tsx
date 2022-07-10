/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import Loadable from "react-loadable";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { registerBeforeUserDropDown } from "@library/headers/mebox/pieces/UserDropdownExtras";

const LoadableUserDropDownContents = Loadable({
    loader: () => import(/* webpackChunkName: "mebox/user" */ "./UserDropDownContentsImpl"),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

const UserDropDownContents: typeof LoadableUserDropDownContents & Record<string, any> = LoadableUserDropDownContents;

UserDropDownContents.registerBeforeUserDropDown = registerBeforeUserDropDown;

export default UserDropDownContents;
