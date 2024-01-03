/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

export const ChangeAuthor = createLoadableComponent({
    loadFunction: () =>
        import(
            /* webpackChunkName: "features/discussions/forms/ChangeAuthorFormForm" */ "@library/features/discussions/forms/ChangeAuthorForm.loadable"
        ),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default ChangeAuthor;
