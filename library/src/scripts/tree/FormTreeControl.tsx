/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { IFormTreeControlLoadableProps } from "@library/tree/FormTreeControl.types";
import { createLoadableComponent } from "@vanilla/react-utils";

export const FormTreeControl = createLoadableComponent<IFormTreeControlLoadableProps>({
    loadFunction: () => import("./FormTreeControl.loadable"),
    fallback: () => <LoadingRectangle width={"100%"} height={200} />,
});
export default FormTreeControl;
