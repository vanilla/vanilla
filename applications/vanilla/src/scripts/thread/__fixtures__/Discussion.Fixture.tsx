/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";

export class DiscussionFixture {
    private static commonFields = {
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-03 17:51:15",
        type: "discussion",
        pinned: false,
        insertUserID: 1,
        insertUser: UserFixture.createMockUser({ userID: 1 }),
        lastUser: UserFixture.createMockUser(),
        closed: false,
        score: 0,
        unread: false,
        countUnread: 0,
        bookmarked: false,
        categoryID: 123,
    };

    public static mockDiscussion: IDiscussion = {
        ...this.commonFields,
        url: "https://vanillaforums.com/discussion/10",
        canonicalUrl: "https://vanillaforums.com/discussion/10",
        name: "Mock Discussion",
        body: "Mock discussion content",
        excerpt: "This is a mock discussion",
        discussionID: 10,
        countViews: 10,
        countComments: 0,
        dateLastComment: "2021-02-17 17:51:15",
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-2 17:51:15",
        type: "discussion",
        pinned: false,
        score: 2,
        resolved: false,
    };
}
