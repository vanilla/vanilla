/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { useUser } from "@library/features/users/userHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty, logError } from "@vanilla/utils";
import PopupUserCard, { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";
import { ButtonTypes } from "@library/forms/buttonTypes";

export interface IUserCardModule {
    userID: number;
    buttonContent?: React.ReactNode; // Second fallback AND button content
    openAsModal?: boolean;
    children?: React.ReactNode; // First fallback
    fallbackButton: React.ReactNode;
    visible?: boolean;
    buttonType?: ButtonTypes;
    buttonClass?: string;
}

// Does not lazy load, will load user data right away
export function UserCardModule(props: IUserCardModule) {
    const { userID, buttonContent, openAsModal, children, fallbackButton, visible } = props;
    const user = useUser({ userID });

    // Fallback to the original link, unchanged
    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(user.status)) {
        return <>{fallbackButton}</>;
    }

    if (!user.data || user.error) {
        if (user.error) {
            logError("failed to fetch data for UserCardModule", user);
        }
        return (
            <>
                {/* Fallback to the original link, unchanged */}
                {children || buttonContent}
            </>
        );
    }

    const userCardInfo: IUserCardInfo = {
        email: user.data.email,
        userID: user.data.userID,
        name: user.data.name,
        photoUrl: user.data.photoUrl,
        dateLastActive: user.data.dateLastActive || undefined,
        dateJoined: user.data.dateInserted,
        label: user.data.label,
        countDiscussions: user.data.countDiscussions || 0,
        countComments: user.data.countComments || 0,
    };

    return (
        <PopupUserCard
            buttonClass={props.buttonClass}
            buttonType={props.buttonType}
            user={userCardInfo}
            buttonContent={buttonContent}
            openAsModal={openAsModal}
            visible={visible}
        />
    );
}
