/**
 * @author Daisy Barrette
 * @copyright 2009-2025
 * @license Proprietary
 */

import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";
import { IConvertPostFormLoadableProps } from "@library/features/discussions/forms/CovertPostForm.types";

export const ConvertPost = createLoadableComponent<IConvertPostFormLoadableProps>({
    loadFunction: () => import("@library/features/discussions/forms/ConvertPostForm.loadable"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default ConvertPost;
