/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, percent, translateX } from "csx";
import { forumLayoutVariables } from "./forumLayoutStyles";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { metasVariables } from "@library/metas/Metas.variables";

import { Mixins } from "@library/styles/Mixins";
import { injectGlobal } from "@emotion/css";

export const groupVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("groups");

    const banner = makeThemeVars("banner", {
        height: 200,
    });

    const logo = makeThemeVars("logo", {
        height: 140,
    });

    return {
        banner,
        logo,
    };
});

export const groupsCSS = () => {
    const vars = groupVariables();
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const layoutVars = forumLayoutVariables();
    const mediaQueries = layoutVars.mediaQueries();

    injectGlobal({
        ".groupSearch": {
            marginBottom: styleUnit(16),
        },
        ".groupToolbar": {
            marginTop: styleUnit(16),
        },
        ".Group-Header": {
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            flexWrap: "wrap",
        },
        ".Group-Header.NoBanner": {
            alignItems: "center",
        },
        ".Group-Banner": {
            height: styleUnit(vars.banner.height),
        },
        ".Group-Header.HasBanner .Group-Banner": {
            ...mediaQueries.mobileDown({
                marginBottom: styleUnit(vars.logo.height / 2 + 10),
            }),
        },
        ".Group-GuestModule": {
            borderTop: "1px solid #dddee0",
            paddingTop: styleUnit(32),
        },
        ".Group-Icon-Big-Wrap": {
            width: styleUnit(vars.logo.height),
            height: styleUnit(vars.logo.height),
            flexBasis: styleUnit(vars.logo.height),
            top: 0,
            background: ColorsUtils.colorOut(globalVars.mainColors.fg),
            zIndex: 1,
            ...Mixins.margin({
                top: 0,
                right: 15,
                left: 0,
                bottom: 10,
            }),
        },
        ".Group-Header.HasBanner .Group-Icon-Big-Wrap": {
            ...mediaQueries.mobileDown({
                position: "absolute",
                top: styleUnit(vars.banner.height - vars.logo.height / 2),
                marginBottom: 0,
                marginLeft: 15,
            }),
        },
        "Group-Header.NoBanner .Group-Icon-Big-Wrap": {
            position: "relative",
            top: "auto",
            marginBottom: 0,
        },
        ".Group-Header-Info": {
            flex: 1,
        },
        ".Group-Header.NoBanner .Group-Header-Info": {
            paddingLeft: styleUnit(0),
        },
        ".Group-Header.HasBanner .Group-Header-Info": {
            paddingLeft: styleUnit(0),
            ...mediaQueries.mobileDown({
                flex: "unset",
            }),
        },
        ...{
            [`
                .ButtonGroup.Open .Button.GroupOptionsTitle::before,
                .Button.GroupOptionsTitle::before,
                .Button.GroupOptionsTitle:active::before,
                .Button.GroupOptionsTitle:focus::before
            `]: {
                color: "inherit",
                marginRight: styleUnit(6),
            },
        },
        "a.ChangePicture": {
            ...absolutePosition.fullSizeOfParent(),
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            opacity: 0,
        },
        ".DataTableContainer.Group-Box.ApplicantList .PageControls .H": {
            position: "relative",
        },
        "body.Section-Event .Group-Banner": {
            flexGrow: 1,
            width: percent(100),
        },
        ".GroupOptions": {
            top: calc(`100% + ${styleUnit(globalVars.gutter.size)}`),
            marginLeft: "auto",
        },
        ".GroupWrap .DataTable .Title-Icon": {
            color: ColorsUtils.colorOut(metasVars.font.color),
        },
        ".Groups .Name.Group-Name .Options .Button": {
            minWidth: 0,
            marginLeft: styleUnit(globalVars.gutter.size),
        },
        ".DataTableContainer.Group-Box": {
            marginTop: styleUnit(globalVars.gutter.size * 3),
        },
        ".Group-Box .PageControls": {
            position: "relative",
            flexDirection: "row",
        },
        ".Group-Box .PageControls .H": {
            margin: 0,
        },
        ".Group-Box.Group-MembersPreview .H": {
            position: "relative",
        },
        ".GroupWrap .DataTable .Buttons": {
            display: "flex",
            flexWrap: "wrap",
            justifyContent: "flex-end",
        },
        ".groupsMemberFilter": {
            marginTop: styleUnit(100),
        },
        ".Event-Title": {
            marginTop: styleUnit(75),
        },
        "body.Groups .NavButton.Handle.GroupOptionsTitle .Sprite": {
            marginRight: negativeUnit(2),
            transform: translateX(`5px`),
        },
        "body.Groups .StructuredForm .Buttons-Confirm": {
            textAlign: "left",
        },
        // Group Box
        ".Group-Box .Item:not(tr)": {
            width: percent(100),
        },
        ".Group-Box .ItemContent": {
            flexGrow: 1,
        },
        ".Groups .DataList .Item > .PhotoWrap": {
            ...absolutePosition.topLeft(13, 8),
            float: "none",
        },
        ".Groups .DataList .Item.hasPhotoWrap .ItemContent": {
            paddingLeft: styleUnit(58),
        },
        ".Groups .DataList .Item.noPhotoWrap .ItemContent": {
            paddingLeft: styleUnit(0),
            paddingRight: styleUnit(70),
        },
        ".Group-Box .Item .Options .Buttons": {
            display: "flex",
            flexWrap: "nowrap",
            alignItems: "center",
        },
        ".Group-Box .Item .Options .Buttons a:first-child": {
            marginRight: styleUnit(4),
        },
        ".DataList .Item.Event.event .DateTile": {
            order: 2,
        },
        ".DataList .Item.Event.event .DateTile + .Options": {
            order: 1,
            ...{
                [`.ToggleFlyout.OptionsMenu`]: {
                    display: "flex",
                    alignItems: "center",
                },
            },
        },
        ".Group-Box .PageControls .Button-Controls": {
            ...mediaQueries.aboveMobile({
                maxHeight: percent(100),
                maxWidth: percent(100),
                margin: "auto 0",
            }),
            ".ButtonGroup + .Button": {
                marginTop: 10,
                display: "block",
            },
        },
        ".Group-Box": {
            marginBottom: styleUnit(36),
        },

        ".Group-Header-Actions": {
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            width: percent(100),
            ...Mixins.margin({
                vertical: styleUnit(globalVars.gutter.size),
            }),
        },
        [`.Group-Header-Actions .Group-Buttons, .Group-Header-Actions .ButtonGroup,`]: {
            position: "relative",
            top: "auto",
        },
        [`.Section-Group .Group-Box .H, .Section-Group .Group-Box .EmptyMessage`]: {
            textAlign: "left",
            ...mediaQueries.tabletDown({
                textAlign: "left",
            }),
            ...mediaQueries.mobileDown({
                marginBottom: styleUnit(6),
            }),
        },
        ".Section-Group .Group-Title": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("title"),
            }),
        },
        ".Section-Group .Group-Box .H": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("subTitle"),
            }),
        },
        ".Button-Controls.Button-Controls": {
            ...mediaQueries.mobileDown({
                display: "block",
                marginTop: 12,
                ...{
                    [`.Button`]: {
                        marginRight: "auto",
                    },
                },
            }),
        },
    });
};
