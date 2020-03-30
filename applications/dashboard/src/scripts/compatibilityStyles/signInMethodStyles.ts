/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { colorOut, importantColorOut } from "@vanilla/library/src/scripts/styles/styleHelpersColors";
import { borders, unit } from "@library/styles/styleHelpers";
import { calc, percent, quote } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { buttonGlobalVariables, buttonVariables } from "@library/forms/buttonStyles";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { formElementsVariables } from "@library/forms/formElementStyles";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export const signInMethodsCSS = useThemeCache(() => {
    const vars = globalVariables();
    const buttonVars = buttonVariables();
    const formElements = formElementsVariables();
    const buttonGlobals = buttonGlobalVariables();
    const textOffset = 11;

    cssOut(`.Methods .SocialIcon .Text`, {
        border: 0,
        paddingLeft: unit(textOffset),
        lineHeight: vars.lineHeights.condensed,
        minWidth: 0,
        maxWidth: calc(`100% - ${unit(formElements.sizing.height - buttonGlobals.padding.horizontal)}`),
        float: "none",
        whiteSpace: "normal",
        textAlign: "left",
        minHeight: 0,
    });

    cssOut(`.SocialIcon.HasText`, {
        lineHeight: 1,
        minWidth: 0,
    });

    cssOut(`.Methods`, {
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
        alignItems: "flex-start",
        flexWrap: "wrap",
    });

    cssOut(
        `
        .Method,
        .Methods-label`,
        {
            display: "block",
            width: percent(100),
        },
    );

    cssOut(`.Method a`, {
        $nest: {
            "&.Button.Primary, &.SocialIcon, &.SocialIcon.HasText": {
                display: "flex",
                justifyContent: "flex-start",
                alignItems: "center",
                flexWrap: "nowrap",
                width: unit(210),
                maxWidth: percent(100),
                ...borders({
                    radius: unit(vars.borderType.formElements.buttons.radius),
                }),
            },
        },
    });

    // // Low specificity here to not style branded options
    // cssOut(`.SocialIcon`, {
    //     color: colorOut(vars.mainColors.fg),
    // });

    const standardSignInMethod = clickableItemStates({
        default: buttonVars.standard.colors ? buttonVars.standard.colors.fg : vars.mainColors.fg,
    });

    // allStates: {},
    // hover: {},
    // focus: {},
    // keyboardFocus: {},
    // active: {},
    // visited: {},

    cssOut(`body.Section-Entry .Methods .SocialIcon`, {
        color: importantColorOut(standardSignInMethod.color as string),
        $nest: standardSignInMethod.$nest,
    });

    // SAML
    cssOut(`body.Section-Entry .Methods .SignInLink.Button.Primary,`, {
        paddingLeft: unit(formElements.sizing.height + textOffset),
    });

    // Workaround for important style in core
    cssOut(
        `
        body.Section-Entry .Methods .SignInLink.Button.Primary,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Facebook,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Twitter,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Google,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-OpenID,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-LinkedIn,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Disqus,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-GitHub
        `,
        {
            color: importantColorOut(vars.elementaryColors.white),
            whiteSpace: "normal",
            textAlign: "left",
            lineHeight: vars.lineHeights.condensed,
            display: "flex",
            alignItems: "center",
            overflow: "hidden",
        },
    );

    cssOut(
        `
        body.Section-Entry .Methods .SignInLink.Button.Primary .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Facebook .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Twitter .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Google .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-OpenID .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-LinkedIn .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-Disqus .Icon::after,
        body.Section-Entry .Methods .SocialIcon.SocialIcon-GitHub .Icon::after`,
        {
            backgroundColor: colorOut(vars.mainColors.primaryContrast.fade(0.2)),
        },
    );

    cssOut(`.Method .SocialIcon .Icon`, {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        minWidth: 0,
        maxWidth: percent(100),
        position: "relative",
        float: "none",
    });

    const lineOffset = 6;
    cssOut(`.Method .Icon::after`, {
        content: quote(``),
        position: "absolute",
        top: unit(lineOffset),
        right: 0,
        width: unit(vars.border.width),
        height: calc(`100% - ${unit(lineOffset * 2)}`),
    });

    cssOut(
        `
        .Method .Button.Primary.SignInLink,
        .Method .SocialIcon
        `,
        {
            paddingTop: unit(4),
            paddingBottom: unit(4),
        },
    );
});
