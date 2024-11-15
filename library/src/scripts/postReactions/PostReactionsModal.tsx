/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import { createLoadableComponent } from "@vanilla/react-utils";

export const PostReactionsModal = createLoadableComponent({
    loadFunction: () => import("@library/postReactions/PostReactionsModal.loadable"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default PostReactionsModal;
