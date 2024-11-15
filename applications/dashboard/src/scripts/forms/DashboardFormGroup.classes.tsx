/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { useThemeCache } from "@library/styles/themeCache";

export const dashboardFormGroupClasses = useThemeCache((compact?: boolean) => {
    const mediaQueries = oneColumnVariables().mediaQueries();
    const globalVars = globalVariables();
    const formGroup = css({
        ...extendItemContainer(18),
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        paddingTop: "16px",
        paddingBottom: "16px",
        marginBottom: 0,
        borderBottom: singleBorder(),
        ...mediaQueries.oneColumnDown({
            flexWrap: "wrap",
        }),
        "&:last-child": {
            borderBottom: "none",
        },
        "&.isJustifiedGroup": {
            justifyContent: "space-between",
        },
        "&.isCompact": {
            borderBottom: "none",
            paddingTop: 8,
            paddingBottom: 8,
        },

        "&.formGroup-radio, &.formGroup-textarea, &.formGroup-select": {
            alignItems: "flex-start",

            "& .modernInputWrap > &:first-child": {
                paddingTop: 0, // To align us with the label.
            },
        },

        "&.isCompact.formGroup-checkBox": {
            marginTop: -16,
        },
        ":not(.formGroup-checkBox) + .formGroup-checkBox": {
            marginTop: 0,
        },
    });

    const labelWrap = css({
        position: "relative",
        minHeight: 1,
        paddingLeft: 18,
        paddingRight: 18,
        minWidth: 0,
        overflow: "hidden",
        flex: "0 0 58.3333333333%",
        ...mediaQueries.oneColumnDown({
            flex: "0 0 100%",
            marginBottom: 4,
        }),
        "&.isVertical": {
            flex: "0 0 100%",
            marginBottom: 4,
        },
        "&.isCompact": {
            flex: "0 0 41%",
        },
    });

    const labelWrapWide = cx(
        labelWrap,
        css({
            flex: "1 0 41.6666666667%",
            "&.isCompact": {
                flex: "1 0 41%",
            },
        }),
    );

    const inputWrap = css({
        position: "relative",
        minHeight: 1,
        paddingLeft: 18,
        paddingRight: 18,
        minWidth: 0,
        flex: "0 0 41.6666666667%",
        "&.isCompact": {
            flex: "0 0 59%",
            display: "flex",
            maxWidth: "59%",
            flexWrap: "wrap",

            "& textarea, & input": {
                fontSize: 13,
                borderColor: ColorsUtils.colorOut(globalVars.border.color),
                "&:focus, &:hover, &:active, &.focus-visible": {
                    borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                },
            },

            "& input": {
                minHeight: 28,
                lineHeight: "28px",
                padding: "0 8px",
                height: "initial",
            },

            "& > button": {
                minHeight: 28,
                lineHeight: "28px",
            },

            "&.isPicker": {
                maxWidth: "100%",
                flex: 1,
            },
        },

        "&.isPicker": {
            paddingLeft: 8,
            paddingRight: 8,
        },

        ...mediaQueries.oneColumnDown({
            flex: "0 0 100%",
            "& input": {
                fontSize: "16px",
            },
        }),

        "&.isInline": {
            display: "flex",
            flexWrap: "wrap",
            alignItems: "center",
            "& label": {
                marginRight: 4,
            },
        },

        "&.isVertical": {
            flexDirection: "column",
        },

        "&.isGrid": {
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            gap: 8,

            "& label": {
                marginTop: 0,
                flexBasis: "calc(50% - 8px)",
                display: "block !important",

                ...mediaQueries.oneColumnDown({
                    flexBasis: "100%",
                    marginTop: 8,
                }),
            },

            "& span": {
                whiteSpace: "normal",
            },
        },

        ".formGroup-checkBox.isCompact &": {
            width: "100%",
            flex: 1,
            maxWidth: "100%",
        },
    });

    const inputWrapRight = cx(
        inputWrap,
        css({
            flex: "initial",
            "&.isCompact": {
                flex: "initial",
            },
        }),
    );

    const inputWrapNone = css({
        position: "relative",
        minHeight: 1,
        paddingLeft: 18,
        paddingRight: 18,
    });

    const labelInfo = css({
        display: "block",
        fontSize: 12,
        lineHeight: 1.4,
        color: ColorsUtils.colorOut(metasVariables().font.color),
        marginTop: 4,
    });

    const vertical = css({
        // In development builds the admin-new stylesheet is added after emotion.
        // We could probably fix that but it would be a real pain and likely cause other styling issues.
        "&&": {
            display: "block",
        },

        [`& .${labelInfo}`]: {
            marginBottom: 8,
        },
    });

    const noBorder = css({
        borderBottom: "none",
        paddingBottom: 8,
    });

    const afterGroup = css({
        width: "100%",
        minWidth: "100%",
    });

    const isNested = css({
        paddingTop: 0,
    });
    return {
        formGroup,
        labelWrap,
        labelWrapWide,
        inputWrap,
        inputWrapRight,
        labelInfo,
        vertical,
        noBorder,
        afterGroup,
        isNested,
        inputWrapNone,
    };
});
