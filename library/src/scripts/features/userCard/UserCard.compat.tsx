/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hasUserViewPermission } from "@library/features/users/modules/hasUserViewPermission";
import { UserCardPopup, useUserCardTrigger } from "@library/features/userCard/UserCard";
import { deconstructAttributesFromElement } from "@vanilla/react-utils";
import { IMountable, mountReactMultiple, useDomNodeAttachment } from "@vanilla/react-utils";
import { logError, notEmpty } from "@vanilla/utils";
import React, { ElementType } from "react";

interface IProps {
    Tag: ElementType;
    tagProps: any;
    domNodesToAttach: Node[];
}

function LegacyUserCardTrigger(props: IProps) {
    const { Tag, tagProps, domNodesToAttach } = props;
    const context = useUserCardTrigger();
    useDomNodeAttachment(domNodesToAttach, context.triggerRef);
    return (
        <Tag {...tagProps} {...context.props} ref={context.triggerRef}>
            {context.contents}
        </Tag>
    );
}

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    if (!hasUserViewPermission()) {
        // No need to mount anything if the user doesn't have permission.
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard:not(.js-userCardInitialized)");
    const mountables: IMountable[] = Array.from(userCards)
        .map((userLink) => {
            const rawUserID = (userLink as HTMLAnchorElement).dataset.userid;
            if (!rawUserID) {
                logError(`No userID for found for js-userCard: `, userLink);
                return null;
            }

            const userID = Number.parseInt(rawUserID);
            if (Number.isNaN(userID)) {
                logError(`Invalid userID \`${rawUserID}\` found for js-userCard: `, userLink);
                return null;
            }

            const attrs = deconstructAttributesFromElement(userLink);
            const Tag = userLink.tagName.toLowerCase() as ElementType;
            attrs.className = attrs.className ?? "";
            attrs.className += " js-userCardInitialized";
            return {
                component: (
                    <UserCardPopup userID={userID}>
                        <LegacyUserCardTrigger
                            Tag={Tag}
                            tagProps={attrs}
                            domNodesToAttach={Array.from(userLink.childNodes)}
                        />
                    </UserCardPopup>
                ),
                target: userLink as HTMLElement,
            };
        })
        .filter(notEmpty);
    mountReactMultiple(mountables, undefined, { overwrite: true });
}
