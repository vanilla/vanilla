/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loadable from "react-loadable";
import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";

export const BulkCloseDiscussions = Loadable({
    loader: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/BulkCloseDiscussionsForm" */ "@library/features/discussions/forms/BulkCloseDiscussionsForm.loadable"
        ),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default BulkCloseDiscussions;
