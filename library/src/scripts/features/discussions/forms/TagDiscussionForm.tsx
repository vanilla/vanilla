/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loadable from "react-loadable";
import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";

export const TagDiscussionForm = Loadable({
    loader: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/TagDiscussionForm" */ "@library/features/discussions/forms/TagDiscussionForm.loadable"
        ),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default TagDiscussionForm;
