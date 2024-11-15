/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const postTypeSettingsClasses = () => {
    const headerClasses = css({
        borderTop: "none",
        th: {
            "&:first-of-type": {
                paddingInlineStart: 19,
            },
        },
    });

    const rowClasses = css({
        td: {
            "&:first-of-type": {
                paddingInlineStart: 19,
            },
        },
    });

    const postLabelLayout = css({
        // Fighting with table styles here
        "&&&": {
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            gap: 8,
        },
    });

    const iconBubble = css({
        background: "#EEEEEE",
        borderRadius: "100%",
        width: 24,
        height: 24,
        padding: 4,
        aspectRatio: "1 / 1",
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const toggleWrapper = css({
        padding: "initial",
    });

    const actionsLayout = css({
        display: "flex",
    });

    const titleLayout = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "space-between",
        alignItems: "center",
        width: "100%",
    });

    const categoryButton = css({
        fontWeight: globalVariables().fonts.weights.normal,
    });

    const postNameLayout = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        gap: 8,
        position: "relative",
    });

    const backLink = css({
        margin: 0,
        transform: "none",
    });

    const sectionHeader = (hasBoarder = true) =>
        css({
            paddingLeft: 16,
            height: 32,
            background: "#FBFCFF",
            display: "flex",
            alignItems: "center",
            textTransform: "uppercase",
            ...(hasBoarder && { borderBottom: "1px solid #e7e8e9" }),
        });

    const emphasisColor = css({
        "&&": {
            color: ColorsUtils.colorOut(globalVariables().messageColors.error.fg),
        },
    });

    const wrappedColumnHeading = css({
        fontWeight: globalVariables().fonts.weights.semiBold,
    });

    const actionButtonsContainer = css({
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        gap: 18,
    });

    const bottomBorderOverride = css({
        borderBottom: "none",
    });

    return {
        headerClasses,
        rowClasses,
        toggleWrapper,
        actionsLayout,
        postLabelLayout,
        iconBubble,
        titleLayout,
        categoryButton,
        postNameLayout,
        backLink,
        sectionHeader,
        emphasisColor,
        wrappedColumnHeading,
        actionButtonsContainer,
        bottomBorderOverride,
    };
};
