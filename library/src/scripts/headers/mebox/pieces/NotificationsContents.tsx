/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { createLoadableComponent } from "@vanilla/react-utils";

const NotificationContents = createLoadableComponent({
    loadFunction: () => import(/* webpackChunkName: "mebox/notifications" */ "./NotificationsContentsImpl"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default NotificationContents;
