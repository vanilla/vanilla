/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "../../../utility/idUtils";
import DropDown from "../../../flyouts/DropDown";
import { t } from "../../../dom/appUtils";
import FrameBody from "../../../layout/frame/FrameBody";
import Frame from "../../../layout/frame/Frame";
import { IUserFragment } from "../../../@types/api/users";
import { UserPhoto, UserPhotoSize } from "library/src/scripts/headers/mebox/pieces/UserPhoto";
import { logError } from "../../../utility/utils";
import { connect } from "react-redux";
import UsersModel, { IInjectableUserState } from "../../../features/users/UsersModel";
import get from "lodash/get";
import { DropDownUserCard } from "../../../flyouts/items/DropDownUserCard";
import DropDownItemSeparator from "../../../flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "../../../flyouts/items/DropDownItemLink";
import DropDownSection from "../../../flyouts/items/DropDownSection";
import DropDownItemLinkWithCount from "../../../flyouts/items/DropDownItemLinkWithCount";
import Permission from "../../../features/users/Permission";
import classNames from "classnames";
import { dummyUserDropDownData } from "../state/dummyUserDropDownData";
import { userDropDownClasses } from "library/src/scripts/headers/mebox/pieces/userDropDownStyles";
import { dropDownClasses } from "../../../flyouts/dropDownStyles";
import { frameBodyClasses } from "../../../layout/frame/frameStyles";
import { vanillaHeaderClasses } from "../../vanillaHeaderStyles";

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
