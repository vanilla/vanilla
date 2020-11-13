/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useUser } from "@library/features/users/userHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { logError } from "@vanilla/utils";
import PopupUserCard, { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Redirect } from "react-router";

export interface IUserCardModule {
    userID: number;
    buttonContent?: React.ReactNode;
    openAsModal?: boolean;
    fallbackButton: React.ReactNode;
    visible?: boolean;
    buttonType?: ButtonTypes;
    buttonClass?: string;
    handleID: string;
    contentID: string;
    userURL: string;
}

// Does not lazy load, will load user data right away
export function UserCardModule(props: IUserCardModule) {
    const { userID, buttonContent, openAsModal, userURL, fallbackButton, visible, handleID, contentID } = props;
    const user = useUser({ userID });

    // Fallback to the original link, unchanged
    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(user.status)) {
        return <>{fallbackButton}</>;
    }

    if (!user.data || user.error) {
        if (user.error) {
            logError("failed to fetch data for UserCardModule", user);
        }
        return <Redirect to={userURL} />; // if there's an error, redirect to the user's profile page.
    }

    const {
        email,
        name,
        photoUrl,
        dateLastActive,
        dateInserted,
        label,
        title,
        countDiscussions = 0,
        countComments = 0,
    } = user.data!;

    const userCardInfo: IUserCardInfo = {
        email,
        userID,
        name,
        photoUrl,
        dateLastActive: dateLastActive || undefined,
        dateJoined: dateInserted,
        label,
        title,
        countDiscussions,
        countComments,
    };

    return (
        <PopupUserCard
            contentID={contentID}
            handleID={handleID}
            buttonClass={props.buttonClass}
            buttonType={props.buttonType}
            user={userCardInfo}
            buttonContent={buttonContent}
            openAsModal={openAsModal}
            visible={visible}
        />
    );
}
