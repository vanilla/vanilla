/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";
import { IPostSettingsProps } from "@library/features/discussions/forms/PostSettings.types";

export const PostSettings = createLoadableComponent<IPostSettingsProps>({
    loadFunction: () => import("@library/features/discussions/forms/PostSettings.loadable"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default PostSettings;
