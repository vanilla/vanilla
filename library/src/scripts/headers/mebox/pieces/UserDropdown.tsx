/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IUserFragment } from "@library/@types/api";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownUserCard from "@library/flyouts/items/DropDownUserCard";
import UsersModel, { IInjectableUserState } from "@library/features/users/UsersModel";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import { vanillaHeaderClasses } from "@library/headers/vanillaHeaderStyles";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import { logError } from "@library/utility/utils";
import { t } from "@library/utility/appUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import Permission from "@library/features/users/Permission";
import { frameBodyClasses } from "@library/layout/frame/frameStyles";
import DropDown from "@library/flyouts/DropDown";
import { dummyUserDropDownData } from "@library/headers/mebox/state/dummyUserDropDownData";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";

export interface IUserDropDownProps extends IInjectableUserState {
    className?: string;
    countsClass?: string;
    buttonClassName?: string;
    contentsClassName?: string;
    toggleContentClassName?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements User Drop down for header
 */
export class UserDropDown extends React.Component<IUserDropDownProps, IState> {
    private id = uniqueIDFromPrefix("userDropDown");

    public state = {
        open: false,
    };

    public render() {
        const userInfo: IUserFragment = get(this.props, "currentUser.data", {
            name: null,
            userID: null,
            photoUrl: null,
        });

        const counts = dummyUserDropDownData;
        const classes = userDropDownClasses();
        const classesDropDown = dropDownClasses();
        const classesFrameBody = frameBodyClasses();
        const classesHeader = vanillaHeaderClasses();

        return (
            <DropDown
                id={this.id}
                name={t("My Account")}
                className={classNames("userDropDown", this.props.className)}
                buttonClassName={classNames("vanillaHeader-account", this.props.buttonClassName)}
                contentsClassName={classNames(
                    "userDropDown-contents",
                    this.props.contentsClassName,
                    classes.contents,
                    classesHeader.dropDownContents,
                )}
                renderLeft={true}
                buttonContents={
                    <div className={classNames("meBox-buttonContent", this.props.toggleContentClassName)}>
                        <UserPhoto
                            userInfo={userInfo}
                            open={this.state.open}
                            className="headerDropDown-user meBox-user"
                            size={UserPhotoSize.SMALL}
                        />
                    </div>
                }
                onVisibilityChange={this.setOpen}
            >
                <Frame>
                    <FrameBody className={classNames(classesFrameBody.root, classesDropDown.verticalPadding)}>
                        <DropDownUserCard currentUser={this.props.currentUser!} className="userDropDown-userCard" />
                        <DropDownItemSeparator />
                        <DropDownItemLink to="/profile/edit" name={t("Edit Profile")} />
                        <DropDownSection title={t("Discussions")}>
                            <DropDownItemLinkWithCount
                                to={`${window.location.origin}/discussions/bookmarked`}
                                name={t("Bookmarks")}
                                count={counts.bookmarkCount}
                            />
                            <DropDownItemLinkWithCount to="/kb/drafts" name={t("Drafts")} count={counts.draftsCount} />
                            <DropDownItemLink to="/discussions/mine" name={t("My Discussions")} />
                            <DropDownItemLink to="/activity" name={t("Participated")} />
                        </DropDownSection>
                        <Permission permission={["community.moderate"]}>
                            <DropDownSection title={t("Moderation")}>
                                <DropDownItemLinkWithCount
                                    to={`${window.location.origin}/dashboard/user/applicants`}
                                    name={t("Applicants")}
                                    count={counts.applicantsCount}
                                    countsClass={this.props.countsClass}
                                />
                                <DropDownItemLinkWithCount
                                    to={`${window.location.origin}/dashboard/log/spam`}
                                    name={t("Spam Queue")}
                                    count={counts.spamQueueCount}
                                    countsClass={this.props.countsClass}
                                />
                                <DropDownItemLinkWithCount
                                    to={`${window.location.origin}/dashboard/log/moderation`}
                                    name={t("Moderation Queue")}
                                    count={counts.moderationQueueCount}
                                    countsClass={this.props.countsClass}
                                />
                                <DropDownItemLinkWithCount
                                    to={`${window.location.origin}/badge/requests`}
                                    name={t("Badge Requests")}
                                    count={counts.badgeRequestCount}
                                    countsClass={this.props.countsClass}
                                />
                            </DropDownSection>
                        </Permission>
                        <DropDownItemSeparator />
                        <DropDownItemLink to={`${window.location.origin}/dashboard/settings`} name={t("Dashboard")} />
                        <DropDownItemLink to={`${window.location.origin}/entry/signout`} name={t("Sign Out")} />
                    </FrameBody>
                </Frame>
            </DropDown>
        );
    }

    private setOpen = open => {
        this.setState({
            open,
        });
    };

    /**
     * @inheritdoc
     */
    public componentDidCatch(error, info) {
        logError(error, info);
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(UserDropDown);
