/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import Permission from "@library/features/users/Permission";
import { useCurrentUser } from "@library/features/users/userHooks";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import SmartLink from "@library/routing/links/SmartLink";
import { getMeta, makeProfileUrl, t } from "@library/utility/appUtils";

export interface IProps {
    className?: string;
    photoSize?: UserPhotoSize;
}

/**
 * Implements DropDownUserCard component for DropDown menus.
 */
export function DropDownUserCard(props: IProps) {
    const currentUser = useCurrentUser()!;
    const customFieldsEnabled = getMeta("featureFlags.CustomProfileFields.Enabled");
    const profileLink = makeProfileUrl(currentUser.userID, currentUser.name);
    const classesUserDropDown = userDropDownClasses.useAsHook();

    return (
        <li className={cx(classesUserDropDown.userCard, "dropDown-userCard", props.className)}>
            <SmartLink
                to={profileLink}
                className={cx("userDropDown-userCardPhotoLink", classesUserDropDown.userCardPhotoLink)}
            >
                <UserPhoto
                    className={cx("userDropDown-userCardPhoto", classesUserDropDown.userCardPhoto)}
                    userInfo={currentUser}
                    size={props.photoSize || UserPhotoSize.LARGE}
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
export default DropDownUserCard;
