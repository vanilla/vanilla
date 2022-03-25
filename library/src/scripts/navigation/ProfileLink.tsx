/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { makeProfileUrl } from "../utility/appUtils";
import classNames from "classnames";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserCardPopup, useUserCardTrigger } from "@library/features/userCard/UserCard";
import { IUserFragment } from "@library/@types/api/users";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps {
    userFragment: Partial<IUserFragment> & Pick<IUserFragment, "userID" | "name">;
    className?: string;
    children?: React.ReactNode;
    isUserCard?: boolean;
    buttonType?: ButtonTypes;
}

/**
 * Class representing a link to a users profile. This will do a full page refresh.
 */
export default function ProfileLink(props: IProps) {
    const { userFragment, isUserCard = true } = props;

    const link = <InnerLink {...props} />;

    if (!isUserCard) {
        return link;
    }

    return (
        <UserCardPopup userID={userFragment.userID} userFragment={userFragment}>
            {link}
        </UserCardPopup>
    );
}

/**
 * Class representing a link to a users profile. This will do a full page refresh.
 */
function InnerLink(props: IProps) {
    const { userFragment, isUserCard = true } = props;
    const children = props.children || userFragment.name;
    const profileURL = makeProfileUrl(userFragment.name);
    const context = useUserCardTrigger();

    return (
        <SmartLink
            {...context.props}
            ref={context.triggerRef as any}
            to={profileURL}
            className={classNames(props.className)}
        >
            {children}
            {context.contents}
        </SmartLink>
    );
}
