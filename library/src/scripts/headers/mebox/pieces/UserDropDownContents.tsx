/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { registerBeforeUserDropDown } from "@library/headers/mebox/pieces/UserDropdownExtras";
import { createLoadableComponent } from "@vanilla/react-utils";

const LoadableUserDropDownContents = createLoadableComponent({
    loadFunction: () => import(/* webpackChunkName: "mebox/user" */ "./UserDropDownContentsImpl"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

const UserDropDownContents: typeof LoadableUserDropDownContents & Record<string, any> = LoadableUserDropDownContents;

UserDropDownContents.registerBeforeUserDropDown = registerBeforeUserDropDown;

export default UserDropDownContents;
