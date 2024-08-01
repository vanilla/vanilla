/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

export const BulkMoveDiscussions = createLoadableComponent({
    loadFunction: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/BulkMoveDiscussionsForm" */ "@library/features/discussions/forms/BulkMoveDiscussionsForm.loadable"
        ),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default BulkMoveDiscussions;
