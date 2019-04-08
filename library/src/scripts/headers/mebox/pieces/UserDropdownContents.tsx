/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownUserCard from "@library/flyouts/items/DropDownUserCard";
import { IInjectableUserState, mapUsersStoreState } from "@library/features/users/userModel";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import { t } from "@library/utility/appUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Permission from "@library/features/users/Permission";
import { dummyUserDropDownData } from "@library/headers/mebox/state/dummyUserDropDownData";
import classNames from "classnames";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { connect } from "react-redux";

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
                    <DropDownUserCard className="userDropDown-userCard" />
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

const withRedux = connect(mapUsersStoreState);
export default withRedux(UserDropdownContents);
