/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import FrameBody from "@library/components/frame/FrameBody";
import Frame from "@library/components/frame/Frame";
import { DropDownUserCard } from "@library/components/dropdown/items/DropDownUserCard";
import DropDownItemSeparator from "@library/components/dropdown/items/DropDownItemSeparator";
import DropDownItemLink from "@library/components/dropdown/items/DropDownItemLink";
import DropDownSection from "@library/components/dropdown/items/DropDownSection";
import DropDownItemLinkWithCount from "@library/components/dropdown/items/DropDownItemLinkWithCount";
import Permission from "@library/users/Permission";
import { IInjectableUserState } from "@library/users/UsersModel";
import { connect } from "react-redux";
import UsersModel from "@library/users/UsersModel";
import classNames from "classnames";
import { dummyUserDropDownData } from "@library/components/mebox/state/dummyUserDropDownData";

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
        return (
            <Frame className={this.props.className}>
                <FrameBody className={classNames("dropDownItem-verticalPadding", this.props.panelBodyClass)}>
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
