/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createLoadableComponent } from "@vanilla/react-utils";

export const PostReactionsModal = createLoadableComponent({
    loadFunction: () => import("@library/postReactions/PostReactionsModal.loadable"),
    fallback() {
        return <></>;
    },
});

export default PostReactionsModal;
