/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import SmartLink from "@library/routing/links/SmartLink";
import { mapUsersStoreState } from "@library/features/users/userModel";
import { IInjectableUserState } from "@library/features/users/userTypes";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import { connect } from "react-redux";
import classNames from "classnames";
import { makeProfileUrl } from "@library/utility/appUtils";

export interface IProps extends IInjectableUserState {
    className?: string;
    photoSize?: UserPhotoSize;
}

/**
 * Implements DropDownUserCard component for DropDown menus.
 */
export class DropDownUserCard extends React.Component<IProps> {
    public render() {
        const currentUser = this.props.currentUser.data!;
        const profileLink = makeProfileUrl(currentUser.name);
        const classesUserDropDown = userDropDownClasses();
        return (
            <li className={classNames(classesUserDropDown.userCard, "dropDown-userCard", this.props.className)}>
                <SmartLink
                    to={profileLink}
                    className={classNames("userDropDown-userCardPhotoLink", classesUserDropDown.userCardPhotoLink)}
                >
                    <UserPhoto
                        className={classNames("userDropDown-userCardPhoto", classesUserDropDown.userCardPhoto)}
                        userInfo={currentUser}
                        size={this.props.photoSize || UserPhotoSize.LARGE}
                    />
                </SmartLink>
                <SmartLink
                    to={profileLink}
                    className={classNames("userDropDown-userCardName", classesUserDropDown.userCardName)}
                >
                    {currentUser.name}
                </SmartLink>
            </li>
        );
    }
}
const withRedux = connect(mapUsersStoreState);
export default withRedux(DropDownUserCard);
