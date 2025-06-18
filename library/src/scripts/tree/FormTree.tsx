/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { createLoadableComponent } from "@vanilla/react-utils";

export const FormTree = createLoadableComponent({
    loadFunction: () => import("./FormTree.loadable"),
    fallback: () => <LoadingRectangle width={"100%"} height={200} />,
}) as typeof import("./FormTree.loadable").default;
export default FormTree;
