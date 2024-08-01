/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { ReactionUrlCode } from "@dashboard/@types/api/reaction";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { STORY_IPSUM_MEDIUM, STORY_TAGS } from "@library/storybook/storyData";

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
        statusID: 1,
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
        statusID: 1,
    };

    public static fakeDiscussions: IDiscussion[] = [
        {
            ...this.commonFields,
            url: "#",
            canonicalUrl: "#",
            name: "Unresolved Discussion",
            excerpt: STORY_IPSUM_MEDIUM,
            discussionID: 10,
            countViews: 10,
            countComments: 0,
            dateLastComment: "2021-02-17 17:51:15",
            dateInserted: "2021-02-11 17:51:15",
            dateUpdated: "2021-02-2 17:51:15",
            type: "discussion",
            pinned: true,
            score: 2,
            resolved: false,
        },
        {
            ...this.commonFields,
            url: "#",
            canonicalUrl: "#",
            name: "Resolved Discussion",
            excerpt: STORY_IPSUM_MEDIUM,
            discussionID: 2,
            countViews: 200,
            countComments: 1299,
            closed: true,
            category: {
                categoryID: 123,
                name: "Product Ideas",
                url: "#",
            },
            resolved: true,
        },
        {
            ...this.commonFields,
            url: "#",
            canonicalUrl: "#",
            name: "With everything",
            excerpt: STORY_IPSUM_MEDIUM,
            discussionID: 5,
            tags: STORY_TAGS,
            countViews: 1029,
            countComments: 11,
            dateInserted: "2021-02-11 17:51:15",
            dateUpdated: "2021-02-11 17:51:15",
            unread: true,
            type: "idea",
            reactions: [
                { urlcode: ReactionUrlCode.UP, reactionValue: 1, hasReacted: false },
                { urlcode: ReactionUrlCode.DOWN, reactionValue: -1, hasReacted: true },
            ],
            score: 22,
        },
        {
            ...this.commonFields,
            url: "#",
            canonicalUrl: "#",
            name: "This is an idea",
            excerpt: STORY_IPSUM_MEDIUM,
            discussionID: 55,
            countViews: 1011,
            countComments: 2,
            dateInserted: "2021-02-11 17:51:15",
            dateUpdated: "2021-02-11 17:51:15",
            unread: true,
            type: "idea",
            reactions: [{ urlcode: ReactionUrlCode.UP, reactionValue: 1, hasReacted: false }],
            score: 333,
        },
    ];
}
