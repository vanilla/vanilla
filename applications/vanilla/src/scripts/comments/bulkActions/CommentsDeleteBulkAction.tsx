/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { createLoadableComponent } from "@vanilla/react-utils";

const CommentsDeleteBulkAction = createLoadableComponent({
    loadFunction: () => import("./CommentsDeleteBulkAction.loadable"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});
export default CommentsDeleteBulkAction;
