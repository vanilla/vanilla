/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { loaderClasses } from "@library/loaders/loaderStyles";
import Loader from "@library/loaders/Loader";
import { createLoadableComponent } from "@vanilla/react-utils";
import { IMovePostFormLoadableProps } from "@library/features/discussions/forms/MovePostForm.types";

const MovePostForm = createLoadableComponent<IMovePostFormLoadableProps>({
    loadFunction: () => import("@library/features/discussions/forms/MovePostForm.loadable"),
    fallback() {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    },
});

export default MovePostForm;
