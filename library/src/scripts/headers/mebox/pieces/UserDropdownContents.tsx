/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "../../../dom/appUtils";
import FrameBody from "../../../layout/frame/FrameBody";
import Frame from "../../../layout/frame/Frame";
import { DropDownUserCard } from "../../../flyouts/items/DropDownUserCard";
import DropDownItemSeparator from "../../../flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "../../../flyouts/items/DropDownItemLink";
import DropDownSection from "../../../flyouts/items/DropDownSection";
import DropDownItemLinkWithCount from "../../../flyouts/items/DropDownItemLinkWithCount";
import Permission from "../../../features/users/Permission";
import { IInjectableUserState } from "../../../features/users/UsersModel";
import { connect } from "react-redux";
import UsersModel from "../../../features/users/UsersModel";
import classNames from "classnames";
import { dummyUserDropDownData } from "../state/dummyUserDropDownData";
import { dropDownClasses } from "../../../flyouts/dropDownStyles";

export interface IUserDropDownContentsProps extends IInjectableUserState {
    countsClass?: string;
    panelBodyClass?: string;
    className?: string;
}

/**
 * Implements User Drop down for header
 */
export class UserDropdownContents extends React.Component<IUserDropDownContentsProps> {
    public render() {
        const counts = dummyUserDropDownData;
        const classesDropDown = dropDownClasses();
        return (
            <Frame className={this.props.className}>
                <FrameBody
                    className={classNames(
                        "dropDownItem-verticalPadding",
                        classesDropDown.verticalPadding,
                        this.props.panelBodyClass,
                    )}
                >
                    <DropDownUserCard currentUser={this.props.currentUser!} className="userDropDown-userCard" />
                    <DropDownItemSeparator />
                    <DropDownItemLink to="/profile/edit" name={t("Edit Profile")} />
                    <DropDownSection title={t("Discussions")}>
                        <DropDownItemLinkWithCount
                            to={`${window.location.origin}/discussions/bookmarked`}
                            name={t("Bookmarks")}
                            count={counts.bookmarkCount}
                        />
                        <DropDownItemLinkWithCount to="/drafts" name={t("Drafts")} count={counts.draftsCount} />
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
        );
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(UserDropdownContents);
