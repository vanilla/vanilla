/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { makeProfileUrl, t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { useState } from "react";
import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { UserIconTypes } from "@library/icons/titleBar";
import { IMeBoxProps } from "@library/headers/mebox/MeBox";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { useSignOutLink } from "@library/contexts/EntryLinkContext";
import LinkAsButton from "@library/routing/LinkAsButton";
import { dashboardMeBoxClasses } from "@library/headers/mebox/pieces/dashboardMeBoxStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { useUser } from "@library/features/users/userHooks";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { getMeta } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { cx } from "@emotion/css";

interface DashboardMeBoxProps extends IMeBoxProps {
    forceOpen?: boolean;
    forceOpenAsModal?: boolean;
    isCompact?: boolean;
}

/**
 * Implements User/Mebox Drop down for header
 */
export default function DashboardMeBox(props: DashboardMeBoxProps) {
    const { currentUser, forceOpen, forceOpenAsModal } = props;
    const userInfo = currentUser.data;
    const user = useUser({ userID: userInfo?.userID as number });
    const signOutUrl = useSignOutLink();
    const siteID = getMeta("context.siteID");
    const isAdmin = userInfo?.isAdmin;
    const classes = dashboardMeBoxClasses();
    const classesTitleBar = titleBarClasses();
    const [isOpen, setOpen] = useState(false);

    const supportLinks = (
        <div className={classes.supportSection}>
            <a href="https://support.vanillaforums.com" className={classes.supportLink}>
                {t("Customer Support")}
                <Icon icon="external-link" size="compact" />
            </a>
        </div>
    );

    return (
        <div className={cx(classes.container, props.isCompact ? classes.mobileContainer : undefined)}>
            <DropDown
                buttonClassName={classesTitleBar.button}
                renderLeft={true}
                buttonContents={
                    <MeBoxIcon compact={false}>
                        <UserPhoto
                            userInfo={userInfo}
                            styleType={UserIconTypes.SELECTED_ACTIVE}
                            className={classNames(classes.userPhoto, "headerDropDown-user meBox-user")}
                            size={UserPhotoSize.SMALL}
                        />
                    </MeBoxIcon>
                }
                flyoutType={FlyoutType.FRAME}
                modalSize={ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT} // this should be revised when doing the mobile version for analytics
                isSmall={true}
                onVisibilityChange={setOpen}
                className={classes.root}
                isVisible={forceOpen}
                openAsModal={forceOpenAsModal}
            >
                <Frame
                    body={
                        <FrameBody className={classes.dropdownBody}>
                            <UserPhoto
                                userInfo={userInfo}
                                styleType={UserIconTypes.DEFAULT}
                                className={classes.dropdownUserPhoto}
                                size={UserPhotoSize.LARGE}
                            />
                            <div className={classes.dropdownUserInfo}>
                                <SmartLink
                                    to={makeProfileUrl(user.data?.name as string)}
                                    className={classes.dropdownUserName}
                                >
                                    {userInfo?.name}
                                </SmartLink>
                                <div className={classes.dropdownUserRank}>{user.data?.rank?.name}</div>
                                <LinkAsButton
                                    to={makeProfileUrl(user.data?.name as string)}
                                    buttonType={ButtonTypes.STANDARD}
                                    className={classes.dropdownProfileLink}
                                >
                                    {t("My Profile")}
                                    <Icon icon="external-link" size="compact" />
                                </LinkAsButton>
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter className={classes.dropdownFooter}>
                            {siteID > 0 && isAdmin && supportLinks}
                            <LinkAsButton
                                to={signOutUrl}
                                buttonType={ButtonTypes.STANDARD}
                                className={classes.signOutButton}
                            >
                                {t("Sign Out")}
                            </LinkAsButton>
                        </FrameFooter>
                    }
                />
            </DropDown>
        </div>
    );
}
