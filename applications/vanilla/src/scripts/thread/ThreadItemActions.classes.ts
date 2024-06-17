/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { metasVariables } from "@library/metas/Metas.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { media } from "@library/styles/styleShim";

const ThreadItemActionsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const root = css(
        {
            ...Mixins.margin({ top: globalVars.gutter.size }),
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "flex-start",
            flexWrap: "wrap",
            gap: 16,
        },
        media(
            { maxWidth: 600 },
            {
                flexDirection: "column",
                alignItems: "flex-start",
                gap: 0,
            },
        ),
    );

    const actionItemsContainer = css({
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        gap: globalVars.gutter.half,
    });

    const actionItem = css({
        display: "inline-flex",
        flexDirection: "row",
        alignItems: "center",
        "&:empty": {
            display: "none",
        },
    });

    const actionButton = css({
        ...Mixins.font(metasVars.font),
        display: "inline-flex",
        alignItems: "center",
        gap: 2,
    });

    return {
        root,
        actionItemsContainer,
        actionItem,
        actionButton,
    };
});

export default ThreadItemActionsClasses;
