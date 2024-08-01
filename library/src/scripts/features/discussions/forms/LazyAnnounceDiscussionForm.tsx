/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

const LazyAnnounceDiscussionForm = createLoadableComponent({
    loadFunction: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/AnnounceDiscussionForm" */ "@library/features/discussions/forms/AnnounceDiscussionForm"
        ),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default LazyAnnounceDiscussionForm;
