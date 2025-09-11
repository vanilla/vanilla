/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITagItem, IGetTagsResponseBody } from "@dashboard/tagging/taggingSettings.types";

// Mock tag fixtures for testing and storybook
// Covers all scenarios: global tags, category-scoped, subcommunity-scoped, mixed scope, zero usage, different types
export const mockTagItems: ITagItem[] = [
    {
        tagID: 1,
        name: "javascript",
        urlcode: "javascript",
        urlCode: "javascript",
        countDiscussions: 245,
        type: "user",
        dateInserted: "2024-01-15T10:30:00Z",
        // Global tag - no categoryIDs or siteSectionIDs
    },
    {
        tagID: 2,
        name: "react",
        urlcode: "react",
        urlCode: "react",
        countDiscussions: 189,
        type: "user",
        dateInserted: "2024-02-20T14:20:00Z",
        scope: {
            categoryIDs: [1, 2, 3],
            allowedCategoryIDs: [1, 2, 3],
        },
    },
    {
        tagID: 3,
        name: "tutorial",
        urlcode: "tutorial",
        urlCode: "tutorial",
        countDiscussions: 78,
        type: "user",
        dateInserted: "2024-03-10T09:15:00Z",
        scope: {
            siteSectionIDs: [10, 11],
        },
    },
    {
        tagID: 4,
        name: "beginner",
        urlcode: "beginner",
        urlCode: "beginner",
        countDiscussions: 156,
        type: "user",
        dateInserted: "2024-01-25T16:45:00Z",
        scope: {
            categoryIDs: [1],
            allowedCategoryIDs: [1],
            siteSectionIDs: [10],
        },
    },
    {
        tagID: 5,
        name: "advanced",
        urlcode: "advanced",
        urlCode: "advanced",
        countDiscussions: 42,
        type: "user",
        dateInserted: "2024-04-05T11:30:00Z",
        scope: {
            categoryIDs: [2, 3, 4, 5, 6],
            allowedCategoryIDs: [2, 3, 4, 5, 6],
        },
    },
    {
        tagID: 6,
        name: "help",
        urlcode: "help",
        urlCode: "help",
        countDiscussions: 0,
        type: "user",
        dateInserted: "2024-05-01T08:00:00Z",
        // Global tag with zero usage
    },
    {
        tagID: 7,
        name: "announcement",
        urlcode: "announcement",
        urlCode: "announcement",
        countDiscussions: 23,
        type: "admin",
        dateInserted: "2024-03-15T12:00:00Z",
        scope: {
            siteSectionIDs: [15, 16, 17, 18, 19, 20],
        },
    },
];

export const mockTagsResponse: IGetTagsResponseBody = {
    data: mockTagItems,
    paging: {
        currentPage: 1,
        limit: 30,
        total: mockTagItems.length,
    },
};
