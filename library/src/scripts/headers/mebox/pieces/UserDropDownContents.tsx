/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@library/apiv2";
import Permission from "@library/features/users/Permission";
import UserActions from "@library/features/users/UserActions";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownUserCard from "@library/flyouts/items/DropDownUserCard";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";

/**
 * Implements User Drop down for header
 */
function UserDropDownContents(props: IProps) {
    const { userInfo } = props;
    if (!userInfo) {
        return null;
    }

    const getCountByName = (countName: string): number => {
        const found = props.counts.find(count => count.name === countName);
        return found ? found.count : 0;
    };

    const classesDropDown = dropDownClasses();

    return (
        <div className={classNames(classesDropDown.verticalPadding, props.className)}>
            <DropDownUserCard className="userDropDown-userCard" />
            <DropDownItemSeparator />
            <DropDownItemLink to="/profile/edit" name={t("Edit Profile")} />
            <Permission permission="articles.add">
                <DropDownSection title={t("Articles")}>
                    <DropDownItemLinkWithCount
                        to="/kb/drafts"
                        name={t("Drafts")}
                        count={getCountByName("ArticleDrafts")}
                    />
                </DropDownSection>
            </Permission>
            <DropDownSection title={t("Discussions")}>
                <DropDownItemLinkWithCount
                    to={"/discussions/bookmarked"}
                    name={t("Bookmarks")}
                    count={getCountByName("Bookmarks")}
                />
                <Permission permission="Discussions.Add">
                    <DropDownItemLinkWithCount to="/drafts" name={t("Drafts")} count={getCountByName("Drafts")} />
                </Permission>
                <DropDownItemLinkWithCount
                    to="/discussions/mine"
                    name={t("My Discussions")}
                    count={getCountByName("Discussions")}
                />
            </DropDownSection>
            <Permission permission={["community.moderate"]}>
                <DropDownSection title={t("Moderation")}>
                    <DropDownItemLinkWithCount
                        to={"/dashboard/user/applicants"}
                        name={t("Applicants")}
                        count={getCountByName("Applications")}
                    />
                    <DropDownItemLinkWithCount
                        to={"/dashboard/log/spam"}
                        name={t("Spam Queue")}
                        count={getCountByName("SpamQueue")}
                    />
                    <DropDownItemLinkWithCount
                        to={"/dashboard/log/moderation"}
                        name={t("Moderation Queue")}
                        count={getCountByName("ModerationQueue")}
                    />
                </DropDownSection>
            </Permission>
            <DropDownItemSeparator />
            <Permission permission={["site.manage", "settings.view"]}>
                <DropDownItemLink to={"/dashboard/settings"} name={t("Dashboard")} />
            </Permission>
            <DropDownItemLink to={`/entry/signout?target=${window.location.href}`} name={t("Sign Out")} />
        </div>
    );
}

interface IOwnProps {
    className?: string;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: ICoreStoreState, ownProps: IOwnProps) {
    return {
        userInfo: state.users.current.data ? state.users.current.data : null,
        counts: state.users.countInformation.counts,
    };
}

function mapDispatchToProps(dispatch: any) {
    const userActions = new UserActions(dispatch, apiv2);
    const { checkCountData } = userActions;
    return {
        checkCountData,
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(UserDropDownContents);
