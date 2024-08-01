/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onReady, t } from "@library/utility/appUtils";
import { addComponent } from "@library/utility/componentRegistry";
import { Toast } from "@library/features/toaster/Toast";
import { ThemePreviewToast } from "@library/features/toaster/themePreview/ThemePreviewToast";
import { themePreviewToastReducer } from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import { registerReducer } from "@library/redux/reducerRegistry";
import { mountPortal } from "@vanilla/react-utils";
import { roleReducer } from "@dashboard/roles/roleReducer";

const PREVIEW_CONTAINER = "previewContainer";

// Routing

registerReducer("themePreviewToaster", themePreviewToastReducer);
registerReducer("roles", roleReducer);

addComponent("toaster", Toast);
onReady(() => {
    mountPortal(<ThemePreviewToast />, PREVIEW_CONTAINER);
});
