/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import {onReady} from "@library/utility/appUtils";
// The forum section needs these legacy scripts that have been moved into the bundled JS so it could be refactored.
// Other sections should not need this yet.
import { addComponent } from "@library/utility/componentRegistry";
import Toast from "@library/features/toaster/Toast";
import {ThemePreviewToast} from "@library/features/toaster/themePreview/ThemePreviewToast";
import {mountModal} from "@library/modal/Modal";
import {themePreviewToastReducer} from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import {registerReducer} from "@library/redux/reducerRegistry";
import {AppContext} from "@library/AppContext";

// Routing

registerReducer("themePreviewToaster", themePreviewToastReducer);
addComponent("toaster", Toast);
onReady(() => {
    const themePreviewContainer = document.body
    mountModal(
        <AppContext noWrap noTheme>
           <ThemePreviewToast />
        </AppContext>
    )
})
