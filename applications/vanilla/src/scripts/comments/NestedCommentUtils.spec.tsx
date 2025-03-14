/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IThreadItem } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { deduplicateNestedItems } from "@vanilla/addon-vanilla/comments/NestedCommentUtils";

describe("NestedCommentUtils", () => {
    it("deduplicateNestedItemss", () => {
        const threadItems: IThreadItem[] = [
            {
                type: "comment",
                commentID: 1,
                parentCommentID: null,
                depth: 0,
                path: "1",
            },
            {
                type: "comment",
                commentID: 2,
                parentCommentID: null,
                depth: 0,
                path: "2",
            },
            {
                type: "comment",
                commentID: 3,
                parentCommentID: null,
                depth: 0,
                path: "3",
            },
            {
                type: "hole",
                parentCommentID: null,
                depth: 0,
                offset: 1,
                insertUsers: [],
                countAllComments: 0,
                countAllInsertUsers: 0,
                apiUrl: "",
                path: "",
            },
        ];

        const duplicate: IThreadItem[] = [
            {
                type: "comment",
                commentID: 2,
                parentCommentID: null,
                depth: 0,
                path: "2",
            },
        ];

        const result = deduplicateNestedItems([...threadItems, ...duplicate]);

        expect(result.length).toBe(threadItems.length);
        expect(result).toMatchObject(threadItems);
    });
});
