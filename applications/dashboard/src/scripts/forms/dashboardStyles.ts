/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { css } from "@emotion/css";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { inputMixinVars } from "@library/forms/inputStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const dashboardClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const _inputMixinVars = inputMixinVars();

    const style = styleFactory("dashboard");

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
    });

    const labelIcon = css({
        marginLeft: 4,
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

    const passwordinput = css({
        "& input": { fontSize: 14 },
    });

    return {
        formList,
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
        labelIcon,
        noLeftPadding,
        helperText,
        passwordinput,
    };
});
