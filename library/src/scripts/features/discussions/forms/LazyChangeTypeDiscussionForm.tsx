/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loadable from "react-loadable";
import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";

const LazyChangeTypeDiscussionForm = Loadable({
    loader: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/ChangeTypeDiscussionForm" */ "@library/features/discussions/forms/ChangeTypeDiscussionForm"
        ),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default LazyChangeTypeDiscussionForm;
