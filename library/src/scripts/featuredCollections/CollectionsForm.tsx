/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 *
 * Loadable forms to add, edit, and remove
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

/**
 * Loadable form to add a record to a collection
 */
export const CollectionsForm = createLoadableComponent({
    loadFunction: () =>
        import(
            /* webpackChunkName: "featuredCollections/CollectionsForm" */ "@library/featuredCollections/CollectionsForm.loadable"
        ),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default CollectionsForm;
