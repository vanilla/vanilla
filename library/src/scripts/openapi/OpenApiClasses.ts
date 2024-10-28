/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { useThemeCache } from "@library/styles/themeCache";

export const openApiClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    return {
        tabRoot: css({
            ...extendItemContainer(12),
        }),
        tabClass: css({
            textTransform: "none",
        }),
        tagGroup: css({
            marginTop: "2em",
            textAlign: "left",
        }),
        endpointSummaryTitle: css({
            "&&": {
                fontWeight: 400,
                textTransform: "uppercase",
                fontSize: 12,
                borderBottom: singleBorder(),
                padding: "8px 16px",
                display: "flex",
                justifyContent: "space-between",
                margin: 0,
            },
        }),
        endpointContainer: css({
            borderRadius: 6,
            border: singleBorder(),
            backgroundColor: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.02)),

            "&.isFocused": {
                boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
            },
        }),
        detailContainer: css({
            marginTop: 12,
            textAlign: "left",
        }),
        paramRow: css({
            display: "flex",
            alignItems: "center",
            gap: 12,
        }),
        parameterLabel: css({
            position: "relative",
            fontWeight: 700,
            ...Mixins.font({
                family: globalVariables().fonts.families.monospace,
                size: 16,
                color: globalVariables().mainColors.fg,
            }),
            whiteSpace: "nowrap",
            fontSize: 14,
        }),
        required: css({
            position: "absolute",
            left: -10,
            top: 2,
            color: ColorsUtils.colorOut(globalVariables().messageColors.error.fg),
        }),
        enumList: css({
            "&&&&": {
                listStyle: "square",
                marginTop: 4,
                marginLeft: 0,
            },
        }),
        enumItem: css({
            "&&&&": {
                listStyle: "square !important",
                marginLeft: "1em",
                ...Mixins.font({ family: globalVariables().fonts.families.monospace }),
            },
        }),
        endpointDetailMethod: css({}),
        endpointHeaderRow: css({
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            "& button": {
                minHeight: 28,
                height: 28,
            },
        }),
        endpointDetailRow: css({
            padding: "8px 16px",
            borderBottom: singleBorder(),
            "&:last-child": {
                borderBottom: "none",
            },
        }),
        endpointDetailRowLabel: css({
            display: "block",
            fontWeight: 600,
            fontSize: 14,
            marginBottom: 4,
        }),
        endpointSummaryList: css({
            "&&": {
                padding: 12,
                margin: 0,
                listStyle: "none",
            },
        }),
        endpointSummaryMethod: css({
            minWidth: 62,
            display: "inline-block",
            textTransform: "uppercase",
            fontWeight: "initial",
            textAlign: "end",
        }),
        endpointSummaryItem: css({
            "&&": {
                ...Mixins.font({ family: globalVars.fonts.families.monospace }),
                listStyle: "none !important",
            },
        }),
        endpointSummaryItemButton: css({
            "&:hover, &:focus": {
                textDecoration: "underline",
                "& *": {
                    textDecoration: "underline",
                },
            },
        }),
        endpointExpander: css({
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            marginTop: 12,
        }),
        noContent: css({
            marginTop: 8,
            fontSize: 14,
        }),
        responseRow: css({
            marginTop: 8,
        }),
        responseLabel: css({
            display: "block",
            fontWeight: 600,
            fontSize: 13,
        }),
    };
});
