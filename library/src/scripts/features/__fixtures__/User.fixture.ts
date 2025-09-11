/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IMe, IUser, type IUserFragment } from "@library/@types/api/users";
import { hashString } from "@vanilla/utils";

export class UserFixture {
    public static adminAsCurrent = {
        status: LoadStatus.SUCCESS,
        data: this.createMockUser({
            userID: 2,
            name: "admin",
            isAdmin: true,
            roleIDs: [],
            roles: [],
            countUnreadNotifications: 0,
            countUnreadConversations: 0,
            emailConfirmed: true,
            showEmail: false,
            bypassSpam: true,
            banned: 0,
            dateInserted: new Date("2022-02-22").toUTCString(),
            dateUpdated: new Date("2022-02-22").toUTCString(),
            hidden: false,
            photoUrl: "#",
            dateLastActive: new Date("2022-02-22").toUTCString(),
        }),
    } as ILoadable<IMe>;

    public static globalAdminPermissions = {
        id: 1,
        type: "global",
        permissions: {
            "advancedNotifications.allow": true,
            "applicants.manage": true,
            "articles.add": true,
            "articles.manage": true,
            "badges.manage": true,
            "badges.moderate": true,
            "badges.request": true,
            "badges.view": true,
            "comments.add": true,
            "comments.delete": true,
            "comments.edit": true,
            "comments.email": true,
            "community.manage": true,
            "community.moderate": true,
            "conversations.email": true,
            "dashboards.manage": true,
            "data.view": true,
            "discussions.add": true,
            "discussions.closeOwn": true,
            "discussions.email": true,
            "discussions.manage": true,
            "discussions.moderate": true,
            "discussions.view": true,
            "email.view": true,
            "emailInvitations.add": true,
            "events.manage": true,
            "events.view": true,
            "flag.add": true,
            "groups.add": true,
            "groups.moderate": true,
            "images.add": true,
            "kb.view": true,
            "personalInfo.view": true,
            "pockets.manage": true,
            "polls.add": true,
            "profilePicture.edit": true,
            "profiles.edit": true,
            "profiles.view": true,
            "reactions.negative.add": true,
            "reactions.positive.add": true,
            "session.valid": true,
            "settings.view": true,
            "site.manage": true,
            "tags.add": true,
            "tokens.add": true,
            "uploads.add": true,
            "users.add": true,
            "users.delete": true,
            "users.edit": true,
        },
    };

    public static createMockUser(overrides?: Partial<IMe>): IMe {
        const name = overrides?.name ?? "Some User";
        let nameHash = hashString(name + "1")
            .toString()
            .slice(0, 32)
            .replace("-", "")
            .toLowerCase();
        nameHash = nameHash.padEnd(32, "0");
        return {
            userID: 3,
            name: name,
            email: "test@example.com",
            emailConfirmed: true,
            showEmail: false,
            bypassSpam: false,
            banned: 0,
            dateInserted: new Date("2022-02-22").toUTCString(),
            dateUpdated: new Date("2022-02-22").toUTCString(),
            hidden: false,
            photoUrl: `https://w${nameHash.slice(0, 1)}.vanillicon.com/v2/${nameHash}.svg`,
            countDiscussions: 10,
            countComments: 100,
            private: false,
            roles: this.getMockRoles(name),
            label: overrides?.name ?? this.getMockTitle(name),
            roleIDs: this.getMockRoles(name).map((role) => role.roleID),
            dateLastActive: new Date("2022-02-22").toUTCString(),
            countUnreadNotifications: 0,
            countUnreadConversations: 0,
            ...overrides,
        } as IMe;
    }

    public static getMockTitle(name: string): string {
        const titles = ["Member", "Super User", "Staff", "Admin"];
        const hash = hashString(name);
        const offset = hash % titles.length;
        return titles[offset]!;
    }

    public static getMockRoles(name: string): IUser["roles"] {
        const roleSets = [
            [
                {
                    roleID: 1,
                    name: "Admin",
                },
            ],
            [
                {
                    roleID: 2,
                    name: "Member",
                },
            ],
            [
                {
                    roleID: 2,
                    name: "Member",
                },
                {
                    roleID: 2,
                    name: "Super User",
                },
            ],
            [
                {
                    roleID: 3,
                    name: "Staff",
                },
                {
                    roleID: 4,
                    name: "Member",
                },
            ],
        ];

        const hash = hashString(name);
        const offset = hash % roleSets.length;
        return roleSets[offset] ?? roleSets[0];
    }
}
