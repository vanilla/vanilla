/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import isNumeric from "validator/lib/isNumeric";
import { logError, notEmpty } from "@vanilla/utils";
import { IMountable, mountReactMultiple } from "@vanilla/react-utils/src";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { UserCardModuleLazyLoad } from "@library/features/users/modules/UserCardModuleLazyLoad";
import { deconstructAttributesFromElement } from "@vanilla/react-utils";
import { important } from "csx";
import { buttonVariables } from "@vanilla/library/src/scripts/forms/Button.variables";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import { numberFormattedClasses } from "@library/content/NumberFormatted.styles";
import { userCardDiscussionPlacement } from "@dashboard/compatibilityStyles/userCards";
import { getMeta } from "@vanilla/library/src/scripts/utility/appUtils";
import { hasUserViewPermission } from "@library/features/users/modules/hasUserViewPermission";

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }

    if (!hasUserViewPermission()) {
        // We don't do user cards if the user's don't have
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard:not(.js-userCardInitialized)");
    const mountables: IMountable[] = Array.from(userCards)
        .map((userLink) => {
            const { userid } = (userLink as HTMLAnchorElement).dataset;
            if (userid && isNumeric(userid) && userLink instanceof HTMLAnchorElement) {
                const userPhoto = userLink.querySelector("img");
                const deconstructedImageData = userPhoto ? deconstructAttributesFromElement(userPhoto) : undefined;
                const parentElement = userLink.parentElement;

                if (parentElement) {
                    const linkClasses = userLink.classList;
                    linkClasses.add("js-userCardInitialized"); // do not target more than once
                    const placeholderElement = document.createElement("span");
                    placeholderElement.classList.add("userCardWrapper");
                    placeholderElement.classList.add(userPhoto ? "userCardWrapper-photo" : "userCardWrapper-link");

                    parentElement.insertBefore(placeholderElement, userLink); // add placeholder before link
                    parentElement.removeChild(userLink);

                    return {
                        component: (
                            <UserCardModuleLazyLoad
                                userID={parseInt(userid)}
                                buttonContent={
                                    deconstructedImageData ? <img {...deconstructedImageData} /> : userLink.innerText
                                }
                                buttonClass={userLink.classList.value}
                                hasImage={!!deconstructedImageData}
                                userURL={userLink.href}
                            />
                        ),
                        target: placeholderElement,
                    };
                }
            } else {
                logError(`Invalid user ID "${userid}" for userlink: `, userLink);
                return null;
            }
        })
        .filter(notEmpty);
    mountReactMultiple(mountables);

    cssOut(
        `
        .Groups .DataTable.CategoryTable tbody td.LatestPost .flyouts,
                .DataTable.CategoryTable tbody td.LatestPost .flyouts
    `,
        {
            display: important("block"),
        },
    );

    cssOut(`.BlockColumn .Block.Wrap`, {
        overflow: "visible",
    });

    cssOut(`.BlockColumn .Block.Wrap .userCardWrapper`, {
        position: "relative",
        maxWidth: "100%",
    });

    cssOut(".userCardWrapper", {
        display: "inline-flex",
    });

    cssOut(`.DataTable .UserLink`, {
        margin: important(0),
    });

    // Do not absolutely position PhotoWrap when it's in a .userCardWrapper
    cssOut(
        `
        .MessageList .ItemComment .Item-Header .userCardWrapper .PhotoWrap,
        .MessageList .ItemDiscussion .Item-Header .userCardWrapper .PhotoWrap
    `,
        {
            position: "relative",
            top: "auto",
            left: "auto",
        },
    );

    // Need to be much more specific than forum css
    const classesUserCard = userCardClasses();
    const buttonVars = buttonVariables();
    const buttonStyles = generateButtonStyleProperties({
        buttonTypeVars: buttonVars.standard,
    });

    const buttonsFromUserCardSelectors = `
        .Groups .DataTable.CategoryTable tbody td.LatestPost a.${classesUserCard.button},
        .DataTable.CategoryTable tbody td.LatestPost a.${classesUserCard.button}
    `;

    cssOut(buttonsFromUserCardSelectors, {
        [`&&&&&&`]: {
            ...buttonStyles,
        },
    });

    const spanInMetaSelectors = `
        .Groups .DataTable.CategoryTable tbody td.LatestPost .Meta .${numberFormattedClasses().root},
        .DataTable.CategoryTable tbody td.LatestPost .Meta .${numberFormattedClasses().root},
    `;

    cssOut(buttonsFromUserCardSelectors, {
        display: "block",
    });

    const LegacyDataDrivenTheme = getMeta("themeFeatures.LegacyDataDrivenTheme", false);
    if (LegacyDataDrivenTheme) {
        userCardDiscussionPlacement();
    }
}
