/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IMe, IUser } from "@library/@types/api/users";

export class UserFixture {
    public static adminAsCurrent = {
        status: LoadStatus.SUCCESS,
        data: {
            userID: 2,
            name: "admin",
            isAdmin: true,
        },
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

    public static createMockUser(overrides?: Partial<IUser>): IUser {
        return {
            userID: 3,
            email: "test@example.com",
            emailConfirmed: true,
            showEmail: false,
            bypassSpam: false,
            banned: 0,
            dateInserted: new Date("2022-02-22").toUTCString(),
            dateUpdated: new Date("2022-02-22").toUTCString(),
            hidden: false,
            countDiscussions: 10,
            countComments: 100,
            private: false,
            ...overrides,
        } as IUser;
    }
}
