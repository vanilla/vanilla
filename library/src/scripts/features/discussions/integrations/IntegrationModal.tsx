/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";

export const IntegrationModal = createLoadableComponent({
    loadFunction: () => import("./IntegrationModal.loadable"),
    fallback(props) {
        const { isVisible } = props;
        return isVisible ? <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} /> : <></>;
    },
});

export default IntegrationModal;
