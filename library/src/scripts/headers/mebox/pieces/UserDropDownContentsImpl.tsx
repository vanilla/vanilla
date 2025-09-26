/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import apiv2 from "@library/apiv2";
import Permission from "@library/features/users/Permission";
import UserActions from "@library/features/users/UserActions";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownUserCard from "@library/flyouts/items/DropDownUserCard";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { getMeta, getSiteSection, t } from "@library/utility/appUtils";
import { cx } from "emotion";
import { connect } from "react-redux";
import { extraUserDropDownComponents } from "@library/headers/mebox/pieces/UserDropdownExtras";
import { useSignOutLink } from "@library/contexts/EntryLinkContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { notEmpty, stableObjectHash } from "@vanilla/utils";

interface AdminItem {
    name: string;
    url: string;
    permissions: string[];
    orPermissions?: string[];
}

const ADMIN_ITEMS: AdminItem[] = [
    {
        name: "Appearance",
        url: "/appearance",
        permissions: ["site.manage", "settings.view"],
    },
    {
        name: "Analytics",
        url: "/analytics",
        permissions: ["site.manage", "settings.view"],
        orPermissions: ["data.view", "dashboards.manage"],
    },
    {
        name: "Settings",
        url: "/dashboard/role",
        permissions: ["site.manage", "settings.view"],
    },
];

/**
 * Implements User Drop down for header
 */
function UserDropDownContentsImpl(props: IUserDropDownContentsProps) {
    const { userInfo } = props;
    const signOutUrl = useSignOutLink();
    const siteSection = getSiteSection();
    const { hasPermission } = usePermissionsContext();

    const makeAdminItems = () => {
        const dropDownItems = ADMIN_ITEMS.map((item) => {
            const permissions = hasPermission(item.permissions) || hasPermission(item.orPermissions ?? []);
            if (permissions) {
                return <DropDownItemLink key={stableObjectHash(item)} to={item.url} name={t(item.name)} />;
            }
        }).filter(notEmpty);
        return <>{dropDownItems.length > 0 && <DropDownSection title={t("Admin")}>{dropDownItems}</DropDownSection>}</>;
    };

    if (!userInfo) {
        return null;
    }

    const getCountByName = (countName: string): number => {
        const found = props.counts.find((count) => count.name === countName);
        return found ? found.count : 0;
    };

    const classesDropDown = dropDownClasses();

    const reportUrl = getMeta("reporting.url", null);
    const betaEnabled = getMeta("featureFlags.CommunityManagementBeta.Enabled", false);
    const cmdEnabled = getMeta("featureFlags.escalations.Enabled", false);

    const moderationItems: React.ReactNode[] = [];
    if (hasPermission("users.approve")) {
        moderationItems.push(
            <DropDownItemLinkWithCount
                to={"/dashboard/user/applicants"}
                name={t("Applicants")}
                count={getCountByName("Applicants")}
            />,
        );
    }

    if (getMeta("triage.enabled", false) && hasPermission("staff.allow")) {
        moderationItems.push(
            <DropDownItemLinkWithCount
                to={"/dashboard/content/triage"}
                name={t("Triage")}
                count={getCountByName("triage")}
            />,
        );
    }

    if (betaEnabled && cmdEnabled && hasPermission(["community.moderate", "posts.moderate"])) {
        moderationItems.push(
            <>
                <DropDownItemLinkWithCount
                    to={"/dashboard/content/reports"}
                    name={t("Reports")}
                    count={getCountByName("reports")}
                />
                <DropDownItemLinkWithCount
                    to={"/dashboard/content/escalations"}
                    name={t("Escalations")}
                    count={getCountByName("escalations")}
                />
            </>,
        );
    }

    if (hasPermission("community.moderate")) {
        if (getCountByName("SpamQueue") > 0 || !cmdEnabled || !betaEnabled) {
            moderationItems.push(
                <DropDownItemLinkWithCount
                    to={"/dashboard/log/spam"}
                    name={t("Spam Queue")}
                    count={getCountByName("SpamQueue")}
                />,
            );
        }

        if (getCountByName("FlaggedQueue") > 0 || !cmdEnabled || !betaEnabled) {
            moderationItems.push(
                <DropDownItemLinkWithCount
                    to={"/dashboard/log/moderation"}
                    name={t("Moderation Queue")}
                    count={getCountByName("ModerationQueue")}
                />,
            );
        }

        if (!cmdEnabled || !betaEnabled) {
            moderationItems.push(
                <>{reportUrl && <DropDownItemLinkWithCount to={reportUrl} name={t("Reported Posts")} />}</>,
            );
        }
    }

    const canScheduleDraft = getMeta("featureFlags.DraftScheduling.Enabled", false) && hasPermission("schedule.allow");

    return (
        <ul className={cx(classesDropDown.verticalPadding, props.className)}>
            <DropDownUserCard className="userDropDown-userCard" />
            {extraUserDropDownComponents.map((ComponentName, index) => {
                return <ComponentName key={index} getCountByName={getCountByName} />;
            })}
            {siteSection.apps.forum ? (
                <DropDownSection title={t("Discussions")}>
                    <DropDownItemLinkWithCount
                        to={"/discussions/bookmarked"}
                        name={t("Bookmarks")}
                        count={getCountByName("Bookmarks")}
                    />
                    <Permission permission="discussions.add">
                        <DropDownItemLinkWithCount
                            to="/drafts"
                            name={canScheduleDraft ? t("Drafts and Scheduled Content") : t("Drafts")}
                            count={getCountByName("Drafts")}
                        />
                    </Permission>
                    <DropDownItemLinkWithCount
                        to="/discussions/mine"
                        name={t("My Posts")}
                        count={getCountByName("Discussions")}
                    />
                </DropDownSection>
            ) : null}
            {moderationItems.length > 0 && (
                <DropDownSection title={t("Moderation")}>
                    {moderationItems.map((item, i) => {
                        return <React.Fragment key={i}>{item}</React.Fragment>;
                    })}
                </DropDownSection>
            )}
            <DropDownItemSeparator />
            {makeAdminItems()}
            <DropDownItemSeparator />
            <DropDownItemLink to={signOutUrl} name={t("Sign Out")} />
        </ul>
    );
}

export interface IUserDropDownContentsOwnProps {
    className?: string;
}

export type IUserDropDownContentsProps = IUserDropDownContentsOwnProps &
    ReturnType<typeof mapStateToProps> &
    ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: ICoreStoreState, ownProps: IUserDropDownContentsOwnProps) {
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

export default connect(mapStateToProps, mapDispatchToProps)(UserDropDownContentsImpl);
