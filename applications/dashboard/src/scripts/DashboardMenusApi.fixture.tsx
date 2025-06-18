/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { DashboardMenusApi } from "@dashboard/DashboardMenusApi";

export class DashboardMenusApiFixture {
    public static mockMenus(): DashboardMenusApi.Section[] {
        return [
            {
                name: "Moderation",
                id: "moderation",
                description: "Community Management",
                url: "/dashboard/content/reports",
                children: [
                    {
                        name: "Posts",
                        id: "content",
                        children: [
                            {
                                name: "Reports",
                                id: "reports",
                                parentID: "moderation",
                                url: "/dashboard/content/reports",
                                react: false,
                            },
                            {
                                name: "Escalations",
                                id: "escalations",
                                parentID: "moderation",
                                url: "/dashboard/content/escalations",
                                react: false,
                            },
                        ],
                    },
                    {
                        name: "Activity & Registration",
                        id: "content-other",
                        children: [
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
                        ],
                    },
                    {
                        name: "Users",
                        id: "users",
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
                                name: "Manage Users",
                                id: "members",
                                parentID: "moderation",
                                url: "/dashboard/user",
                                react: false,
                            },
                        ],
                    },
                    {
                        name: "Settings",
                        id: "settings",
                        children: [
                            {
                                name: "Messages",
                                id: "messages",
                                parentID: "moderation",
                                url: "/dashboard/message",
                                react: false,
                            },
                            {
                                name: "Content Settings",
                                id: "content",
                                parentID: "moderation",
                                url: "/dashboard/content/settings",
                                react: false,
                            },
                            {
                                name: "Premoderation Settings",
                                id: "premoderation",
                                parentID: "moderation",
                                url: "/dashboard/content/premoderation",
                                react: false,
                            },
                            {
                                name: "Ban Rules",
                                id: "bans",
                                parentID: "moderation",
                                url: "/dashboard/settings/bans",
                                react: false,
                            },
                            {
                                name: "Flood Control",
                                id: "flood-control",
                                parentID: "moderation",
                                url: "/vanilla/settings/floodcontrol",
                                react: false,
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
                name: "Analytics",
                id: "analytics",
                description: "Visualize Your Community",
                url: "/dashboard/settings/home",
                children: [],
            },
            {
                name: "Appearance",
                id: "appearance",
                description: "Customize your community",
                url: "/appearance",
                children: [],
            },
            {
                name: "Settings",
                id: "settings",
                description: "Configuration & Addons",
                url: "/dashboard/role",
                children: [
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
                                name: "User Preferences",
                                id: "preferences",
                                parentID: "settings",
                                url: "/dashboard/settings/preferences",
                                react: false,
                            },
                            {
                                name: "Interests & Suggested Content",
                                id: "interests",
                                parentID: "settings",
                                url: "/settings/interests",
                                react: false,
                            },
                            {
                                name: "Avatars",
                                id: "avatars",
                                parentID: "settings",
                                url: "/dashboard/settings/avatars",
                                react: false,
                            },
                        ],
                    },
                    {
                        name: "Emails",
                        id: "email",
                        children: [
                            {
                                name: "Email Settings",
                                id: "settings",
                                parentID: "settings",
                                url: "/dashboard/settings/email",
                                react: false,
                            },
                            {
                                name: "Digest Settings",
                                id: "digest",
                                parentID: "settings",
                                url: "/dashboard/settings/digest",
                                react: false,
                            },
                        ],
                    },
                    {
                        name: "Posts",
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
                                name: "Post Types",
                                id: "post-types",
                                parentID: "settings",
                                url: "/settings/post-types",
                                react: false,
                            },
                            {
                                name: "Reactions",
                                id: "reactions",
                                parentID: "settings",
                                url: "/reactions",
                                react: false,
                            },
                            {
                                name: "Tagging",
                                id: "tagging",
                                parentID: "settings",
                                url: "/settings/tagging",
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
                                url: "/embed/forum",
                                react: false,
                            },
                            {
                                name: "Language Settings",
                                id: "languages",
                                parentID: "settings",
                                url: "/settings/language",
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
                                name: "Audit Log",
                                id: "audit-log",
                                parentID: "settings",
                                url: "/dashboard/settings/audit-logs",
                                react: false,
                                badge: {
                                    type: "text",
                                    text: "New",
                                },
                            },
                            {
                                name: "Routes",
                                id: "routes",
                                parentID: "settings",
                                url: "/dashboard/routes",
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
                                id: "api-docs",
                                parentID: "settings",
                                url: "/settings/api-docs",
                                react: false,
                                badge: {
                                    type: "text",
                                    text: "v2",
                                },
                            },
                        ],
                    },
                ],
            },
            {
                name: "Vanilla Staff",
                id: "vanillastaff",
                description: "Vanilla Staff",
                url: "/utility/structure",
                children: [
                    {
                        name: "Developer",
                        id: "developer",
                        children: [
                            {
                                name: "Database Migrations",
                                id: "dbaStructure",
                                parentID: "vanillastaff",
                                url: "/utility/structure",
                                react: false,
                            },
                            {
                                name: "Aggregate Counts",
                                id: "dbaCounts",
                                parentID: "vanillastaff",
                                url: "/dba/counts",
                                react: false,
                            },
                            {
                                name: "Performance Profiles",
                                id: "profiles",
                                parentID: "vanillastaff",
                                url: "/settings/vanilla-staff/profiles",
                                react: false,
                            },
                        ],
                    },
                    {
                        name: "Product",
                        id: "product",
                        children: [
                            {
                                name: "Manage Messages",
                                id: "messages",
                                parentID: "vanillastaff",
                                url: "/settings/vanilla-staff/product-messages",
                                react: false,
                            },
                        ],
                    },
                ],
            },
        ];
    }
}
