/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

const LazyChangeTypeDiscussionForm = createLoadableComponent({
    loadFunction: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/ChangeTypeDiscussionForm" */ "@library/features/discussions/forms/ChangeTypeDiscussionForm"
        ),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default LazyChangeTypeDiscussionForm;
