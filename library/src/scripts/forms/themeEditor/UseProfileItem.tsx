import React from "react";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { IUserFragment } from "@library/@types/api/users";
import { CheckCompactIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n/src";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { Button } from "@storybook/components";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {
    name?: string;
    imageUrl?: string;
    date?: string;
    isSelected?: boolean;
    userInfo: IUserFragment;
    revisionID: number;
    onClick?: (event: any) => void;
}

export function UserProfileItem(props: IProps) {
    //const currentUser = this.props.currentUser.data!;
    // const profileLink = `${window.location.origin}/profile/${currentUser.name}`;
    const classesUserDropDown = userDropDownClasses();
    const visibilityClasses = visibility();

    return (
        <>
            <UserPhoto userInfo={props.userInfo} size={UserPhotoSize.MEDIUM} />
            <DropDownSwitchButton
                onClick={props.onClick}
                label={`${props.name} ${props.date}`}
                status={props.isSelected}
            />
        </>
    );
}
