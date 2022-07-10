/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import Loadable from "react-loadable";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";

const MessagesContents = Loadable({
    loader: () => import(/* webpackChunkName: "mebox/messages" */ "./MessagesContentsImpl"),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default MessagesContents;
