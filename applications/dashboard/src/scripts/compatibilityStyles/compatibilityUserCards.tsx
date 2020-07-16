/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import isNumeric from "validator/lib/isNumeric";
import { logError } from "@vanilla/utils";
import { mountReact } from "@vanilla/react-utils/src";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import { UserCardModuleLazyLoad } from "@library/features/users/modules/UserCardModuleLazyLoad";
import { hasPermission } from "@vanilla/library/src/scripts/features/users/Permission";

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }

    if (!hasPermission("profiles.view")) {
        // We don't do user cards if the user's don't have
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard");
    userCards.forEach(userLink => {
        const { userid } = (userLink as HTMLAnchorElement).dataset;
        if (userid && isNumeric(userid) && userLink instanceof HTMLAnchorElement) {
            const linkClasses = userLink.classList;
            linkClasses.remove("js-userCard"); // do not target more than once

            if (userLink.parentElement) {
                const placeholderElement = document.createElement("span");
                placeholderElement.classList.add("userCardWrapper");
                userLink.parentElement.replaceChild(placeholderElement, userLink); // I couldn't get mountReact to replace the link directly
                mountReact(
                    <UserCardModuleLazyLoad userID={parseInt(userid)} buttonContent={<>{userLink.innerText}</>}>
                        <SmartLink
                            to={userLink.href}
                            title={userLink.title}
                            rel={userLink.rel}
                            target={userLink.target}
                            className={classNames(linkClasses.value, userCardClasses().link)}
                        >
                            {userLink.innerText}
                        </SmartLink>
                    </UserCardModuleLazyLoad>,
                    placeholderElement,
                    undefined,
                );
            }
        } else {
            logError(`Invalid user ID "${userid}" for userlink: `, userLink);
        }
    });

    cssOut(".userCardWrapper", {
        display: "inline-flex",
        zIndex: 1,
    });
}
