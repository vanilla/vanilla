/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

export const Tokens = createLoadableComponent({
    loadFunction: () => import(/* webpackChunkName: "forms/select/Tokens" */ "@library/forms/select/Tokens.loadable"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});
