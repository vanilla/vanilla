/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { makeProfileUrl } from "../utility/appUtils";
import classNames from "classnames";
import { UserCardModule } from "@library/features/users/modules/UserCardModule";
import { UserCardModuleLazyLoad } from "@library/features/users/modules/UserCardModuleLazyLoad";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { hasPermission } from "@library/features/users/Permission";

interface IProps {
    username: string;
    userID: number;
    className?: string;
    children?: React.ReactNode;
    isUserCard?: boolean;
    cardAsModal?: boolean;
    buttonType?: ButtonTypes;
}

/**
 * Class representing a link to a users profile. This will do a full page refresh.
 */
export default function ProfileLink(props: IProps) {
    const { username, isUserCard = true, cardAsModal } = props;
    const children = props.children || username;
    if (isUserCard && hasPermission("profiles.view")) {
        return (
            <UserCardModuleLazyLoad
                buttonType={props.buttonType}
                buttonContent={children}
                openAsModal={cardAsModal}
                userID={props.userID}
                buttonClass={props.className}
            />
        );
    } else {
        return (
            <a href={makeProfileUrl(username)} className={classNames(props.className)}>
                {children}
            </a>
        );
    }
}
