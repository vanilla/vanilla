import React from "react";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";

interface IProps {
    name: string;
    imageUrl?: string;
    date?: string;
    isSelected?: boolean;
    onClick?: () => void;
}

export function UserProfileItem(props: IProps) {
    //const currentUser = this.props.currentUser.data!;
    // const profileLink = `${window.location.origin}/profile/${currentUser.name}`;
    const classesUserDropDown = userDropDownClasses();
    return (
        <li className={classNames(classesUserDropDown.userCard, "dropDown-userCard", this.props.className)}>
            <SmartLink
                to={}
                className={classNames("userDropDown-userCardPhotoLink", classesUserDropDown.userCardPhotoLink)}
            >
                <UserPhoto
                    className={classNames("userDropDown-userCardPhoto", classesUserDropDown.userCardPhoto)}
                    userInfo={}
                    size={UserPhotoSize.LARGE}
                />
            </SmartLink>
            <SmartLink to={} className={classNames("userDropDown-userCardName", classesUserDropDown.userCardName)}>
                {}
            </SmartLink>
        </li>
    );
}
