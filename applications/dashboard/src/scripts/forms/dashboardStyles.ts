/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { css } from "@emotion/css";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";

export const dashboardClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const style = styleFactory("dashboard");

    const subHeading = css({
        ...extendItemContainer(18),
        paddingLeft: 18,
        paddingRight: 18,
        marginTop: -1,
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        fontWeight: 600,
        fontSize: 14,
        lineHeight: 1.5,
    });

    const subHeadingActions = css({
        display: "flex",
        alignItems: "center",
        gap: 8,
        "& button, & [role='button']": {
            minHeight: 28,
            lineHeight: "26px",
        },
    });

    const subHeadingBackground = style({
        "&.subheading": {
            fontSize: "14px",
            marginBottom: "0",
            paddingTop: 9,
            paddingBottom: 9,
            borderTop: "1px solid #D8D8D8",
            borderBottom: "1px solid #D8D8D8",
            backgroundColor: "#f6f9fb",
            textTransform: "uppercase",
            maxHeight: 41,
        },
    });

    const formList = style({
        padding: 0,
    });

    const extendRow = css({
        ...extendItemContainer(18),
    });

    const helpAsset = style("helpAsset", {
        fontSize: "inherit !important",
        marginBottom: globalVars.gutter.size,
    });

    const tokenInput = style("tokenInput", {
        fontSize: "inherit",
    });

    const selectOne = style("selectOne", {
        ...{
            [`&.SelectOne__value-container.inputText.inputText`]: {
                fontSize: "inherit",
            },
        },
    });

    const formListItem = css({
        minHeight: 49,
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        borderBottom: singleBorder(),
        "& span:first-of-type": {
            marginRight: "auto",
        },
    });

    const formListItemTitle = css({
        fontSize: globalVars.fonts.size.medium,
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const formListItemStatus = css({
        display: "inline-block",
        fontSize: globalVars.fonts.size.small,
        fontWeight: globalVars.fonts.weights.normal,
        marginTop: globalVars.fonts.size.medium - globalVars.fonts.size.small,
        color: "#767676",
        marginRight: 30,
        "&:last-of-type": {
            marginRight: 53,
        },
    });

    const formListItemAction = css({
        "& svg": {
            maxWidth: 21,
        },
    });

    const extendBottomBorder = css({
        position: "relative",
        "&:before, &:after": {
            content: "''",
            display: "block",
            width: 18,
            height: "100%",
            borderBottom: singleBorder(),
            position: "absolute",
            bottom: -1,
        },
        "&:before": {
            left: -18,
        },
        "&:after": {
            right: -18,
        },
    });

    const mediaAddonListItem = css({
        minHeight: 102,
        display: "flex",
        padding: "16px 0",
        alignItems: "center",
        borderBottom: singleBorder(),

        "& .mediaAddonListItem_icon": {
            marginRight: 14,
            borderRadius: 4,
            /**
             * The color "f6f9fb" already exists in dashboard/scss/src/_variables.scss,
             * We may need to move them into globalVars or create a shared "dashboardVars"
             */
            backgroundColor: "#f6f9fb",
            overflow: "hidden",
            "& img": {
                maxHeight: 84,
                width: "auto",
                minWidth: 84,
            },
        },

        "& .mediaAddonListItem_details": {
            maxWidth: "80ch",
            marginRight: "auto",
            "& h3": {
                fontSize: globalVars.fonts.size.medium,
                marginBottom: globalVars.spacer.headingItem,
            },
        },

        "& .mediaAddonListItem_config": {
            marginRight: 19,
        },
    });

    const disabled = css({
        pointerEvents: "none",
        opacity: 0.5,
        cursor: "not-allowed",
        '& div[class^="toggle"]': {
            cursor: "not-allowed",
        },
    });

    const colorInput = css({
        "&&": {
            marginTop: 0,
            borderRadius: "6px 0 0 6px",
        },
    });

    const swatch = css({
        "&&": {
            border: "1px solid",
            borderColor: ColorsUtils.colorOut(globalVars.borderType.formElements.default.color),
            borderRadius: "0 6px 6px 0",
            borderLeft: 0,
        },
    });

    const label = css({
        display: "inline-flex",
        alignItems: "center",
        ...Mixins.font({
            weight: globalVars.fonts.weights.semiBold,
        }),
        ...Mixins.margin({
            bottom: 0,
        }),
    });

    const labelRequired = css({
        position: "absolute",
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        left: 8,
    });

    const labelIcon = css({
        marginLeft: 4,
        marginTop: 2,
    });

    const noLeftPadding = css({
        "&&": {
            paddingLeft: 0,
        },
    });

    const helperText = css({
        display: "block",
        fontSize: 12,
        lineHeight: 1.3333333333,
        color: "#949aa2",
        marginTop: "1em",
    });

    const passwordInput = css({
        "& input": { fontSize: 14 },
    });

    const spaceBetweenFormGroup = css({
        justifyContent: "space-between",

        "& .input-wrap": {
            flex: 0,
        },
    });

    const buttonRow = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        paddingInline: 10,

        "& .label-wrap": {
            // Create the 58.3333333% used from the old 24 col grid system css
            flex: `0 0 ${(100 / 24) * 14}%`,
        },

        "& h1, h2, h3, h4": {
            marginTop: 9,
        },

        ...mediaQueries.oneColumnDown({
            display: "block",
        }),
    });

    const inputWrapper = css({
        display: "flex",
        width: "100%",
        "& > input": {
            flex: 1,
        },
    });

    const multiLineInput = css({
        "& textarea": {
            fontSize: "14px",
        },
    });

    const currencyInput = css({
        display: "flex",
        alignItems: "center",

        "& .dollar": {
            position: "relative",
            left: "25px",
            fontSize: 16,
            fontWeight: globalVars.fonts.weights.semiBold,
        },

        "& input": {
            paddingLeft: "35px",
        },
    });

    const readonlyRow = css({
        paddingTop: 8,
        paddingBottom: 8,
    });
    const readonlyTokens = css({
        display: "flex",
        alignItems: "center",
        flexWrap: "wrap",
        gap: 8,
        paddingTop: 8,
        paddingBottom: 8,
    });
    const readonlyDescription = css({});
    const readonlyEmptyMessage = css({});
    const ratioInputContainer = css({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "space-between",
        maxWidth: "7em",

        "& input": {
            textAlign: "center",
        },

        "@media (max-width: 300px)": {
            flexDirection: "column",
        },
    });

    const ratioInputReadOnlyNumerator = css({
        padding: "6px 6px 6px 0",
    });

    const ratioInput = css({
        maxWidth: "4em",
        padding: 6,
    });

    const ratioInputSeparator = css({
        padding: 6,
        minWidth: "max-content",
    });

    const highlight = css({
        backgroundColor: ColorsUtils.colorOut(globalVars.messageColors.error.bg.fade(0.25)),
    });

    return {
        subHeading,
        subHeadingBackground,
        subHeadingActions,
        formList,
        inputWrapper,
        helpAsset,
        tokenInput,
        selectOne,
        extendRow,
        formListItem,
        formListItemTitle,
        formListItemStatus,
        formListItemAction,
        extendBottomBorder,
        mediaAddonListItem,
        disabled,
        colorInput,
        swatch,
        label,
        labelRequired,
        labelIcon,
        noLeftPadding,
        helperText,
        passwordInput,
        spaceBetweenFormGroup,
        buttonRow,
        currencyInput,
        multiLineInput,
        readonlyRow,
        readonlyTokens,
        readonlyDescription,
        readonlyEmptyMessage,
        ratioInputContainer,
        ratioInputReadOnlyNumerator,
        ratioInput,
        ratioInputSeparator,
        highlight,
    };
});
