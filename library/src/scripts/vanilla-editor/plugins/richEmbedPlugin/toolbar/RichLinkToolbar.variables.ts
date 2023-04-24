/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export const linkToolbarVariables = useThemeCache(() => {
    return {
        menuBar: {
            maxWidth: 350,
        },
        linkPreviewMenuBarItem: {
            maxWidth: 260,
            spacing: Variables.spacing({ left: 8 }),
        },

        form: {
            spacing: Variables.spacing({ horizontal: 16, vertical: 8 }),
        },

        externalIcon: {
            size: 20,
        },
    };
});
