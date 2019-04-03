/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import Permission from "@library/features/users/Permission";
import UsersModel, { IInjectableUserState } from "@library/features/users/UsersModel";
import DropDown from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownUserCard from "@library/flyouts/items/DropDownUserCard";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { dummyUserDropDownData } from "@library/headers/mebox/state/dummyUserDropDownData";
import { vanillaHeaderClasses } from "@library/headers/vanillaHeaderStyles";
import { frameBodyClasses } from "@library/layout/frame/frameStyles";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { logError } from "@library/utility/utils";
import classNames from "classnames";
import React from "react";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { connect } from "react-redux";

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
        const userInfo = this.props.currentUser.data;
        if (!userInfo) {
            return null;
        }

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
                        <DropDownUserCard className="userDropDown-userCard" />
                        <DropDownItemSeparator />
                        <DropDownItemLink to="/profile/edit" name={t("Edit Profile")} />
                        <DropDownSection title={t("Discussions")}>
                            <DropDownItemLinkWithCount
                                to={"/discussions/bookmarked"}
                                name={t("Bookmarks")}
                                count={counts.bookmarkCount}
                            />
                            <Permission permission="articles.add">
                                <DropDownItemLinkWithCount
                                    to="/kb/drafts"
                                    name={t("Drafts")}
                                    count={counts.draftsCount}
                                />
                            </Permission>
                            <DropDownItemLink to="/discussions/mine" name={t("My Discussions")} />
                            <DropDownItemLink to="/activity" name={t("Participated")} />
                        </DropDownSection>
                        <Permission permission={["community.moderate"]}>
                            <DropDownSection title={t("Moderation")}>
                                <DropDownItemLinkWithCount
                                    to={"/dashboard/user/applicants"}
                                    name={t("Applicants")}
                                    count={counts.applicantsCount}
                                    countsClass={this.props.countsClass}
                                />
                                <DropDownItemLinkWithCount
                                    to={"/dashboard/log/spam"}
                                    name={t("Spam Queue")}
                                    count={counts.spamQueueCount}
                                    countsClass={this.props.countsClass}
                                />
                                <DropDownItemLinkWithCount
                                    to={"/dashboard/log/moderation"}
                                    name={t("Moderation Queue")}
                                    count={counts.moderationQueueCount}
                                    countsClass={this.props.countsClass}
                                />
                                <DropDownItemLinkWithCount
                                    to={"/badge/requests"}
                                    name={t("Badge Requests")}
                                    count={counts.badgeRequestCount}
                                    countsClass={this.props.countsClass}
                                />
                            </DropDownSection>
                        </Permission>
                        <DropDownItemSeparator />
                        <Permission permission={["site.manage", "settings.view"]}>
                            <DropDownItemLink to={"/dashboard/settings"} name={t("Dashboard")} />
                        </Permission>
                        <DropDownItemLink to={`/entry/signout?target=${window.location.href}`} name={t("Sign Out")} />
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
