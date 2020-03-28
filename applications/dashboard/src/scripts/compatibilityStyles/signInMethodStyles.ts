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
import { percent } from "csx";

import { cssOut } from "@dashboard/compatibilityStyles/index";
import { mixinButton } from "@dashboard/compatibilityStyles/buttonStylesCompat";
import { ButtonTypes, buttonVariables } from "@library/forms/buttonStyles";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { formElementsVariables } from "@library/forms/formElementStyles";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export const signInMethodsCSS = useThemeCache(() => {
    const vars = globalVariables();
    const buttonVars = buttonVariables();
    const formElements = formElementsVariables();

    cssOut(`.Methods .SocialIcon .Text`, {
        borderLeftColor: colorOut(vars.mainColors.primaryContrast.fade(0.2)),
        paddingLeft: unit(11),
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
                minWidth: unit(210),
                width: percent(100),
                minWidth: 0,
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
        paddingLeft: unit(formElements.sizing.height),
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
        },
    );

    cssOut(
        `
        .Method .SocialIcon .Text,
        .Method .SocialIcon .Icon
    `,
        {
            minWidth: 0,
            maxWidth: percent(100),
            float: "none",
        },
    );
});
