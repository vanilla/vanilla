/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Loadable from "react-loadable";
import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";

export const BulkMergeDiscussionsForm = Loadable({
    loader: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/BulkMergeDiscussionsForm" */ "@library/features/discussions/forms/BulkMergeDiscussionsForm.loadable"
        ),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});
