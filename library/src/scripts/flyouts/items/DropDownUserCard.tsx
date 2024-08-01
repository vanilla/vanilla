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
import { getMeta, makeProfileUrl, t } from "@library/utility/appUtils";
import Permission from "@library/features/users/Permission";
import { cx } from "@emotion/css";

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
        const customFieldsEnabled = getMeta("featureFlags.CustomProfileFields.Enabled");
        const profileLink = makeProfileUrl(currentUser.userID, currentUser.name);
        const classesUserDropDown = userDropDownClasses();

        return (
            <li className={cx(classesUserDropDown.userCard, "dropDown-userCard", this.props.className)}>
                <SmartLink
                    to={profileLink}
                    className={cx("userDropDown-userCardPhotoLink", classesUserDropDown.userCardPhotoLink)}
                >
                    <UserPhoto
                        className={cx("userDropDown-userCardPhoto", classesUserDropDown.userCardPhoto)}
                        userInfo={currentUser}
                        size={this.props.photoSize || UserPhotoSize.LARGE}
                    />
                </SmartLink>
                <div className={classesUserDropDown.userInfo}>
                    <SmartLink
                        to={profileLink}
                        className={cx("userDropDown-userCardName", classesUserDropDown.userCardName)}
                    >
                        {currentUser.name}
                    </SmartLink>
                    <span className={classesUserDropDown.email}>{currentUser?.email}</span>
                    <Permission
                        permission={"profiles.edit"}
                        fallback={
                            <>
                                <SmartLink className={classesUserDropDown.accountLinks} to={"/profile/preferences"}>
                                    {t("Edit Preferences")}
                                </SmartLink>
                            </>
                        }
                    >
                        <SmartLink
                            className={classesUserDropDown.accountLinks}
                            to={customFieldsEnabled ? "/profile/account-privacy" : "/profile/edit"}
                        >
                            {t(customFieldsEnabled ? "Account & Privacy Settings" : "Edit Profile")}
                        </SmartLink>
                    </Permission>
                </div>
            </li>
        );
    }
}
const withRedux = connect(mapUsersStoreState);
export default withRedux(DropDownUserCard);
