/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { deduplicateThreadItems } from "@vanilla/addon-vanilla/thread/threadUtils";

describe("threadUtils", () => {
    it("deduplicateThreadItems", () => {
        const threadItems: IThreadItem[] = [
            {
                type: "comment",
                commentID: 1,
                parentCommentID: null,
                depth: 0,
            },
            {
                type: "comment",
                commentID: 2,
                parentCommentID: null,
                depth: 0,
            },
            {
                type: "comment",
                commentID: 3,
                parentCommentID: null,
                depth: 0,
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
            },
        ];

        const result = deduplicateThreadItems([...threadItems, ...duplicate]);

        expect(result.length).toBe(threadItems.length);
        expect(result).toMatchObject(threadItems);
    });
});
