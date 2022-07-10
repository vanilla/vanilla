/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import DashboardTitleBar from "@dashboard/components/DashboardTitleBar";
import { testStoreState } from "@library/__tests__/testStoreState";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { setMeta } from "@library/utility/appUtils";
import { useEffect } from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Heading from "@library/layout/Heading";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Headers/Dashboard Title Bar",
};

const mockSections = [
    {
        name: "Moderation",
        id: "moderation",
        description: "Community Management",
        url: "/dashboard/user",
        children: [
            {
                name: "Site",
                id: "site",
                children: [
                    {
                        name: "Messages",
                        id: "messages",
                        parentID: "moderation",
                        url: "/dashboard/message",
                        react: false,
                    },
                    {
                        name: "Users",
                        id: "users",
                        parentID: "moderation",
                        url: "/dashboard/user",
                        react: false,
                    },
                    {
                        name: "Ban Rules",
                        id: "bans",
                        parentID: "moderation",
                        url: "/dashboard/settings/bans",
                        react: false,
                    },
                ],
            },
            {
                name: "Requests",
                id: "requests",
                children: [
                    {
                        name: "Applicants",
                        id: "applicants",
                        parentID: "moderation",
                        url: "/dashboard/user/applicants",
                        react: false,
                        badge: {
                            type: "view",
                            url: "/dashboard/user/applicantcount",
                        },
                    },
                    {
                        name: "Badge Requests",
                        id: "badges-give",
                        parentID: "moderation",
                        url: "/badge/requests",
                        react: false,
                    },
                ],
            },
            {
                name: "Content",
                id: "content",
                children: [
                    {
                        name: "Flood Control",
                        id: "flood-control",
                        parentID: "moderation",
                        url: "/vanilla/settings/floodcontrol",
                        react: false,
                    },
                    {
                        name: "Spam Queue",
                        id: "spam-queue",
                        parentID: "moderation",
                        url: "/dashboard/log/spam",
                        react: false,
                    },
                    {
                        name: "Moderation Queue",
                        id: "moderation-queue",
                        parentID: "moderation",
                        url: "/dashboard/log/moderation",
                        react: false,
                        badge: {
                            type: "view",
                            url: "/dashboard/log/count/moderate",
                        },
                    },
                    {
                        name: "Change Log",
                        id: "change-log",
                        parentID: "moderation",
                        url: "/dashboard/log/edits",
                        react: false,
                    },
                ],
            },
        ],
    },
    {
        name: "Settings",
        id: "settings",
        description: "Configuration & Addons",
        url: "/dashboard/role",
        children: [
            {
                name: "Appearance",
                id: "appearance",
                children: [
                    {
                        name: "Branding & SEO",
                        id: "banner",
                        parentID: "settings",
                        url: "/dashboard/settings/branding",
                        react: false,
                    },
                    {
                        name: "Themes",
                        id: "themes",
                        parentID: "settings",
                        url: "/dashboard/settings/themes",
                        react: false,
                    },
                    {
                        name: "Avatars",
                        id: "avatars",
                        parentID: "settings",
                        url: "/dashboard/settings/avatars",
                        react: false,
                    },
                    {
                        name: "Email",
                        id: "email",
                        parentID: "settings",
                        url: "/dashboard/settings/emailstyles",
                        react: false,
                    },
                    {
                        name: "Pockets",
                        id: "pockets",
                        parentID: "settings",
                        url: "settings/pockets",
                        react: false,
                    },
                ],
            },
            {
                name: "Membership",
                id: "users",
                children: [
                    {
                        name: "Roles & Permissions",
                        id: "roles",
                        parentID: "settings",
                        url: "/dashboard/role",
                        react: false,
                    },
                    {
                        name: "Registration",
                        id: "registration",
                        parentID: "settings",
                        url: "/dashboard/settings/registration",
                        react: false,
                    },
                    {
                        name: "User Profile",
                        id: "profile",
                        parentID: "settings",
                        url: "/dashboard/settings/profile",
                        react: false,
                    },
                    {
                        name: "Badges",
                        id: "badges",
                        parentID: "settings",
                        url: "/badge/all",
                        react: false,
                    },
                    {
                        name: "Spoof",
                        id: "spoof",
                        parentID: "settings",
                        url: "user/spoof",
                        react: false,
                    },
                    {
                        name: "Profile Fields",
                        id: "profile-fields",
                        parentID: "settings",
                        url: "settings/profileextender",
                        react: false,
                    },
                    {
                        name: "Ranks",
                        id: "ranks",
                        parentID: "settings",
                        url: "settings/ranks",
                        react: false,
                    },
                ],
            },
            {
                name: "Discussions",
                id: "forum",
                children: [
                    {
                        name: "Categories",
                        id: "manage-categories",
                        parentID: "settings",
                        url: "/vanilla/settings/categories",
                        react: false,
                    },
                    {
                        name: "Posting",
                        id: "posting",
                        parentID: "settings",
                        url: "/vanilla/settings/posting",
                        react: false,
                    },
                    {
                        name: "Rules",
                        id: "rules",
                        parentID: "settings",
                        url: "/settings/rules",
                        react: false,
                    },
                    {
                        name: "Tagging",
                        id: "tagging",
                        parentID: "settings",
                        url: "settings/tagging",
                        react: false,
                    },
                    {
                        name: "Idea Statuses",
                        id: "idea-statuses",
                        parentID: "settings",
                        url: "/dashboard/settings/statuses",
                        react: false,
                    },
                    {
                        name: "Reactions",
                        id: "reactions",
                        parentID: "settings",
                        url: "reactions",
                        react: false,
                    },
                ],
            },
            {
                name: "Connections",
                id: "connect",
                children: [
                    {
                        name: "Social Media",
                        id: "social",
                        parentID: "settings",
                        url: "/social/manage",
                        react: false,
                    },
                ],
            },
            {
                name: "Addons",
                id: "add-ons",
                children: [
                    {
                        name: "Plugins",
                        id: "plugins",
                        parentID: "settings",
                        url: "/dashboard/settings/plugins",
                        react: false,
                    },
                    {
                        name: "Applications",
                        id: "applications",
                        parentID: "settings",
                        url: "/dashboard/settings/applications",
                        react: false,
                    },
                    {
                        name: "Labs",
                        id: "labs",
                        parentID: "settings",
                        url: "/settings/labs",
                        react: false,
                        badge: {
                            type: "text",
                            text: "New",
                        },
                    },
                ],
            },
            {
                name: "Technical",
                id: "site-settings",
                children: [
                    {
                        name: "Embedding",
                        id: "embed-site",
                        parentID: "settings",
                        url: "embed/forum",
                        react: false,
                    },
                    {
                        name: "Language Settings",
                        id: "languages",
                        parentID: "settings",
                        url: "/settings/language",
                        react: false,
                        badge: {
                            type: "text",
                            text: "New",
                        },
                    },
                    {
                        name: "Outgoing Email",
                        id: "email",
                        parentID: "settings",
                        url: "/dashboard/settings/email",
                        react: false,
                    },
                    {
                        name: "Security",
                        id: "security",
                        parentID: "settings",
                        url: "/dashboard/settings/security",
                        react: false,
                    },
                    {
                        name: "Routes",
                        id: "routes",
                        parentID: "settings",
                        url: "/dashboard/routes",
                        react: false,
                    },
                    {
                        name: "Statistics",
                        id: "statistics",
                        parentID: "settings",
                        url: "/dashboard/statistics",
                        react: false,
                    },
                ],
            },
            {
                name: "API Integrations",
                id: "api",
                children: [
                    {
                        name: "API",
                        id: "swagger-ui",
                        parentID: "settings",
                        url: "/settings/swagger",
                        react: false,
                        badge: {
                            type: "text",
                            text: "v2",
                        },
                    },
                    {
                        name: "Webhooks",
                        id: "webhook-settings",
                        parentID: "settings",
                        url: "/webhook-settings",
                        react: false,
                    },
                ],
            },
            {
                name: "Knowledge",
                id: "knowledge",
                children: [
                    {
                        name: "Knowledge Bases",
                        id: "knowledge-bases",
                        parentID: "settings",
                        url: "/knowledge-settings/knowledge-bases",
                        react: false,
                    },
                    {
                        name: "General Appearance",
                        id: "general-appearance",
                        parentID: "settings",
                        url: "/knowledge-settings/general-appearance",
                        react: false,
                    },
                ],
            },
        ],
    },
];

const makeMockRegisterUserInfo: IMe = {
    name: "Neena",
    userID: 1,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
};

const dumbDashboardTreeItems = [
    { children: [], name: "Some Link", parentID: 0, recordID: 1, recordType: "dashboardItem", sort: 0, url: "#" },
    { children: [], name: "Another link", parentID: 0, recordID: 2, recordType: "dashboardItem", sort: 0, url: "#" },
];

const dumbHumburgerContent = (
    <>
        <hr className={dropDownClasses().separator} />
        <Heading title={"Some Content Section"} className={dropDownClasses().sectionHeading} />
        <DropDownPanelNav
            navItems={dumbDashboardTreeItems}
            isNestable={false}
            activeRecord={{
                recordID: "notspecified",
                recordType: "customLink",
            }}
        />
    </>
);

const initialState = testStoreState({
    users: {
        current: {
            status: LoadStatus.SUCCESS,
            data: makeMockRegisterUserInfo,
        },
        permissions: {
            status: LoadStatus.SUCCESS,
            data: {
                isAdmin: true,
                permissions: [],
            },
        },
    },
});

export const RegularDashboardTitleBar = storyWithConfig({ useWrappers: false }, () => {
    return <DashboardTitleBar sections={mockSections} />;
});

export const TitleBarWithMeboxOpen = storyWithConfig({ useWrappers: false }, () => {
    useEffect(() => {
        setMeta("context.siteID", -1);
    });
    return (
        <Provider store={getStore(initialState, true)}>
            <DashboardTitleBar forceMeBoxOpen={true} sections={mockSections} />
        </Provider>
    );
});

export const MeboxOpenWithAccountAndSupportLinks = storyWithConfig({ useWrappers: false }, () => {
    useEffect(() => {
        setMeta("context.siteID", 1);
    });
    return (
        <Provider store={getStore(initialState, true)}>
            <DashboardTitleBar forceMeBoxOpen={true} sections={mockSections} />
        </Provider>
    );
});

export const TitleBarWithHamburgerOpenOnSmallerViews = storyWithConfig(
    {
        useWrappers: false,
    },
    () => {
        useEffect(() => {
            setMeta("context.siteID", 1);
        });
        return (
            <Provider store={getStore(initialState, true)}>
                <DashboardTitleBar
                    hamburgerContent={dumbHumburgerContent}
                    isCompact={true}
                    forceHamburgerOpen={true}
                    sections={[]}
                />
            </Provider>
        );
    },
);

export const MeboxOpenOnSmallerViews = storyWithConfig({ useWrappers: false }, () => {
    useEffect(() => {
        setMeta("context.siteID", 1);
    });
    return (
        <Provider store={getStore(initialState, true)}>
            <DashboardTitleBar
                forceMeBoxOpen={true}
                forceMeBoxOpenAsModal={true}
                isCompact={true}
                sections={mockSections}
            />
        </Provider>
    );
});
