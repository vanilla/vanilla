/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { createLoadableComponent } from "@vanilla/react-utils";

export const ReportModal = createLoadableComponent({
    loadFunction: () => import("./ReportModal.loadable"),
    fallback(props) {
        if (!props.isVisible) {
            return <></>;
        }
        return <Loader size={100} loaderStyleClass={loaderClasses().smallLoader} />;
    },
});

export default ReportModal;
