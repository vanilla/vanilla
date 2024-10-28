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

    const root = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        gap: 16,

        "@container threadItemContainer (width < 500px)": {
            columnGap: 16,
            rowGap: 8,
        },
    });

    const actionItemsContainer = css({
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        gap: globalVars.gutter.size,

        "@container threadItemContainer (width < 516px)": {
            gridColumn: "1/2",
            gridRow: "2/3",
            justifySelf: "start",
            alignSelf: "end",
        },
    });

    const reactionItemsContainer = css({
        "@container threadItemContainer (width < 516px)": {
            gridColumn: "1/3",
            gridRow: "1/2",
        },
    });

    const actionItem = css({
        display: "inline-flex",
        flexDirection: "row",
        alignItems: "center",
        "&:empty": {
            display: "none",
        },
    });

    const quoteButton = css({
        alignSelf: "center",
    });

    const actionButton = css({
        ...Mixins.font(metasVars.font),
        display: "inline-flex",
        alignItems: "center",
        gap: 2,
        "@media (max-width : 516px)": {
            fontSize: globalVars.fontSizeAndWeightVars("medium").size,
            fontWeight: globalVars.fontSizeAndWeightVars("medium").weight,
        },
    });

    return {
        root,
        reactionItemsContainer,
        actionItemsContainer,
        actionItem,
        quoteButton,
        actionButton,
    };
});

export default ThreadItemActionsClasses;
