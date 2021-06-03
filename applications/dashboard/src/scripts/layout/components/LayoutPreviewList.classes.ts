/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { css } from "@emotion/css";

const layoutPreviewListClasses = useThemeCache(() => {
    const heading = css("heading", {
        // Fighting admin.css
        "&&": {
            marginTop: 32,
        },
    });

    return { heading };
});

export default layoutPreviewListClasses;
