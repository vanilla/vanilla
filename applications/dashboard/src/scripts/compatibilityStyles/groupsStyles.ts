/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    absolutePosition,
    colorOut,
    importantColorOut,
    negativeUnit,
    paddings,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, percent, translateX } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { forumLayoutVariables } from "./forumLayoutStyles";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { metaContainerStyles, metaItemStyle } from "@library/styles/metasStyles";

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
        height: unit(vars.banner.height),
    });

    cssOut(`.groupToolbar`, {
        marginTop: unit(32),
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
            marginRight: unit(6),
        },
    );

    cssOut(`.Group-Header.NoBanner .Group-Header-Info`, {
        paddingLeft: unit(0),
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

    cssOut(`.Photo.PhotoWrap.PhotoWrapLarge.Group-Icon-Big-Wrap`, {
        width: unit(vars.logo.height),
        height: unit(vars.logo.height),
        flexBasis: unit(vars.logo.height),
        top: unit(vars.banner.height - vars.logo.height / 2),
        background: "transparent",
        zIndex: 1,
        $nest: {
            "&:hover .ChangePicture": {
                opacity: 1,
            },
        },
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

    cssOut(`.Groups .DataTable .Item td, .DataTable .Item td`, {
        borderBottom: singleBorder(),
        ...paddings({
            ...layoutVars.cell.paddings,
        }),
        backgroundColor: "transparent",
    });

    cssOut(`.Groups .DataTable .Item:first-child td, .DataTable .Item:first-child td`, {
        borderTop: singleBorder(),
    });

    cssOut(`.GroupOptions`, {
        top: calc(`100% + ${unit(globalVars.gutter.size)}`),
    });

    cssOut(`.PhotoWrap:hover a.ChangePicture`, {
        opacity: 0,
        backgroundColor: importantColorOut(globalVars.elementaryColors.black.fade(0.4)),
        color: colorOut(globalVars.elementaryColors.white),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
    });

    cssOut(`.GroupWrap .DataTable .Title-Icon`, {
        color: colorOut(globalVars.meta.colors.fg),
    });

    cssOut(
        `.Groups .DataTable .Excerpt, .Groups .DataTable .CategoryDescription, .DataTable .Excerpt, .DataTable .CategoryDescription`,
        {
            color: colorOut(globalVars.mainColors.fg),
            fontSize: unit(globalVars.fonts.size.medium),
        },
    );

    cssOut(`.Groups .Name.Group-Name .Options .Button`, {
        minWidth: 0,
        marginLeft: unit(globalVars.gutter.size),
    });

    cssOut(`.DataTableContainer.Group-Box`, {
        marginTop: unit(globalVars.gutter.size * 3),
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

    cssOut(
        `.Group-Box .PageControls .H`,
        {
            margin: 0,
        },
        mediaQueries.xs({
            position: "relative",
            top: "auto",
            left: "auto",
            right: "auto",
        }),
    );

    cssOut(`.Group-Box.Group-MembersPreview .H`, {
        position: "relative",
    });

    cssOut(`.GroupWrap .DataTable .Buttons`, {
        display: "flex",
        flexWrap: "wrap",
        justifyContent: "flex-end",
    });

    cssOut(`.groupsMemberFilter`, {
        marginTop: unit(100),
    });

    cssOut(`.Event-Title`, {
        marginTop: unit(75),
    });

    cssOut(`body.Groups .Group-Content .Meta`, metaContainerStyles());
    cssOut(`body.Groups .Group-Content .Meta .MItem`, {
        ...metaItemStyle(),
    });

    cssOut(`body.Groups .Button.Handle .Sprite, body.Groups .NavButton.Handle .Sprite`, {
        marginRight: negativeUnit(2),
        transform: translateX(`5px`),
    });

    cssOut(`body.Groups .StructuredForm .Buttons-Confirm`, {
        textAlign: "left",
    });

    // Group Box
    cssOut(`.Group-Box .Item:not(tr)`, {
        display: "flex",
        flexDirection: "row-reverse",
        width: percent(100),
        alignItems: "center",
    });

    cssOut(`.Group-Box .ItemContent`, {
        flexGrow: 1,
    });

    cssOut(`.Groups .DataList .Item > .PhotoWrap`, {
        ...absolutePosition.topLeft(13, 8),
        float: "none",
    });

    cssOut(`.Groups .DataList .ItemContent`, {
        order: 11,
    });

    cssOut(`.Groups .DataList .Item.hasPhotoWrap .ItemContent`, {
        paddingLeft: unit(58),
    });

    cssOut(`.Groups .DataList .Item.noPhotoWrap .ItemContent`, {
        paddingLeft: unit(0),
    });

    cssOut(`.Group-Box .Item .Options .Buttons`, {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
    });

    cssOut(`.Group-Box .Item .Options .Buttons a:first-child`, {
        marginRight: unit(4),
    });

    cssOut(`.DataList .Item.Event.event .DateTile`, {
        order: 2,
    });

    cssOut(`.DataList .Item.Event.event .DateTile + .Options`, {
        order: 1,
        $nest: {
            [`& .ToggleFlyout.OptionsMenu`]: {
                display: "flex",
                alignItems: "center",
            },
        },
    });

    cssOut(`.Group-Box .PageControls .Button-Controls`, {
        ...absolutePosition.middleRightOfParent(),
    });

    cssOut(`.Group-Box`, {
        marginBottom: unit(36),
    });
};
