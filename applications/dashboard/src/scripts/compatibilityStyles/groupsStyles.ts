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
import { calc, important, percent, translateX } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { forumLayoutVariables } from "./forumLayoutStyles";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { metaContainerStyles, metaItemStyle } from "@library/styles/metasStyles";
import { Mixins } from "@library/styles/Mixins";

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
    const layoutVars = forumLayoutVariables();
    const mediaQueries = layoutVars.mediaQueries();

    cssOut(`.Group-Banner`, {
        height: styleUnit(vars.banner.height),
    });

    cssOut(`.groupToolbar`, {
        marginTop: styleUnit(32),
    });

    cssOut(
        `
        .ButtonGroup.Open .Button.GroupOptionsTitle::before,
        .Button.GroupOptionsTitle::before,
        .Button.GroupOptionsTitle:active::before,
        .Button.GroupOptionsTitle:focus::before
        `,
        {
            color: "inherit",
            marginRight: styleUnit(6),
        },
    );

    cssOut(`.Group-Header.NoBanner .Group-Header-Info`, {
        paddingLeft: styleUnit(0),
    });

    cssOut(`.Group-Header`, {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        flexWrap: "wrap",
    });

    cssOut(`a.ChangePicture`, {
        ...absolutePosition.fullSizeOfParent(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        opacity: 0,
    });

    cssOut(`.DataTableContainer.Group-Box.ApplicantList .PageControls .H`, {
        position: "relative",
    });

    cssOut(`body.Section-Event .Group-Banner`, {
        flexGrow: 1,
        width: percent(100),
    });

    cssOut(`.PhotoWrap.PhotoWrapLarge.Group-Icon-Big-Wrap`, {
        width: styleUnit(vars.logo.height),
        height: styleUnit(vars.logo.height),
        flexBasis: styleUnit(vars.logo.height),
        top: styleUnit(vars.banner.height - vars.logo.height / 2),
        background: "transparent",
        zIndex: 1,
    });

    cssOut(`.Group-Header.NoBanner .Group-Icon-Big-Wrap`, {
        position: "relative",
        top: "auto",
        float: "none",
        marginBottom: 0,
    });

    cssOut(`.Group-Header.NoBanner`, {
        alignItems: "center",
    });

    cssOut(`.GroupOptions`, {
        top: calc(`100% + ${styleUnit(globalVars.gutter.size)}`),
        marginLeft: "auto",
    });

    cssOut(`.GroupWrap .DataTable .Title-Icon`, {
        color: ColorsUtils.colorOut(globalVars.meta.colors.fg),
    });

    cssOut(`.Groups .Name.Group-Name .Options .Button`, {
        minWidth: 0,
        marginLeft: styleUnit(globalVars.gutter.size),
    });

    cssOut(`.DataTableContainer.Group-Box`, {
        marginTop: styleUnit(globalVars.gutter.size * 3),
    });

    cssOut(
        `
        .Group-Box .PageControls
        `,
        {
            position: "relative",
            flexDirection: "row",
        },
    );

    cssOut(`.Group-Box .PageControls .H`, {
        margin: 0,
    });

    cssOut(`.Group-Box.Group-MembersPreview .H`, {
        position: "relative",
    });

    cssOut(`.GroupWrap .DataTable .Buttons`, {
        display: "flex",
        flexWrap: "wrap",
        justifyContent: "flex-end",
    });

    cssOut(`.groupsMemberFilter`, {
        marginTop: styleUnit(100),
    });

    cssOut(`.Event-Title`, {
        marginTop: styleUnit(75),
    });

    cssOut(`body.Groups .Group-Content .Meta`, metaContainerStyles());
    cssOut(`body.Groups .Group-Content .Meta .MItem`, {
        ...metaItemStyle(),
    });

    cssOut(
        `
        body.Groups .NavButton.Handle.GroupOptionsTitle .Sprite
    `,
        {
            marginRight: negativeUnit(2),
            transform: translateX(`5px`),
        },
    );

    cssOut(`body.Groups .StructuredForm .Buttons-Confirm`, {
        textAlign: "left",
    });

    // Group Box
    cssOut(`.Group-Box .Item:not(tr)`, {
        width: percent(100),
    });

    cssOut(`.Group-Box .ItemContent`, {
        flexGrow: 1,
    });

    cssOut(`.Groups .DataList .Item > .PhotoWrap`, {
        ...absolutePosition.topLeft(13, 8),
        float: "none",
    });

    cssOut(`.Groups .DataList .Item.hasPhotoWrap .ItemContent`, {
        paddingLeft: styleUnit(58),
    });

    cssOut(`.Groups .DataList .Item.noPhotoWrap .ItemContent`, {
        paddingLeft: styleUnit(0),
        paddingRight: styleUnit(70),
    });

    cssOut(`.Group-Box .Item .Options .Buttons`, {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
    });

    cssOut(`.Group-Box .Item .Options .Buttons a:first-child`, {
        marginRight: styleUnit(4),
    });

    cssOut(`.DataList .Item.Event.event .DateTile`, {
        order: 2,
    });

    cssOut(`.DataList .Item.Event.event .DateTile + .Options`, {
        order: 1,
        ...{
            [`.ToggleFlyout.OptionsMenu`]: {
                display: "flex",
                alignItems: "center",
            },
        },
    });

    cssOut(
        `.Group-Box .PageControls .Button-Controls`,
        mediaQueries.aboveMobile({
            maxHeight: percent(100),
            maxWidth: percent(100),
            margin: "auto 0",
        }),
    );

    cssOut(`.Group-Box`, {
        marginBottom: styleUnit(36),
    });

    cssOut(`.Group-Header-Actions`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        width: percent(100),
        ...Mixins.margin({
            vertical: styleUnit(globalVars.gutter.size),
        }),
    });
    cssOut(
        `
        .Group-Header-Actions .Group-Buttons,
        .Group-Header-Actions .ButtonGroup,
    `,
        {
            position: "relative",
            top: "auto",
        },
    );

    cssOut(
        `.Section-Group .Group-Box .H,
        .Section-Group .Group-Box .EmptyMessage`,
        {
            textAlign: "left",
        },
        mediaQueries.tabletDown({
            textAlign: "left",
        }),
        mediaQueries.mobileDown({
            marginBottom: styleUnit(6),
        }),
    );

    cssOut(`.Section-Group .Group-Title`, {
        fontSize: globalVars.fonts.size.title,
    });

    cssOut(`.Section-Group .Group-Box .H`, {
        fontSize: globalVars.fonts.size.subTitle,
    });

    cssOut(
        `
        .Button-Controls.Button-Controls
    `,
        mediaQueries.mobileDown({
            display: "block",
            ...{
                [`.Button`]: {
                    marginRight: "auto",
                },
            },
        }),
    );
};
