/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 *
 * Loadable forms to add, edit, and remove
 */

import React from "react";
import Loadable from "react-loadable";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";

/**
 * Loadable form to add a record to a collection
 */
export const CollectionsForm = Loadable({
    loader: () =>
        import(
            /* webpackChunkName: "featuredCollections/CollectionsForm" */ "@library/featuredCollections/CollectionsForm.loadable"
        ),
    loading() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default CollectionsForm;
