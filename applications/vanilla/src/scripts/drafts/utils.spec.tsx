/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import {
    getParamsFromPath,
    makeCommentDraft,
    MakeCommentDraftParams,
    makeCommentDraftProps,
    makePostDraft,
    MakePostDraftParams,
} from "@vanilla/addon-vanilla/drafts/utils";

describe("Draft Utils", () => {
    beforeAll(() => {
        const date = new Date(2025, 1, 15);
        vi.useFakeTimers();
        vi.setSystemTime(date);
    });
    it("getParamsFromPath: Create Path", () => {
        const expected = {
            type: "create",
            postType: "custom-post-type-id",
            parentRecordType: "category",
            parentRecordID: "parent-record-id",
        };
        const result = getParamsFromPath(`/post/${expected.postType}/${expected.parentRecordID}`, "");
        expect(result).toEqual(expected);
    });
    it("getParamsFromPath: Edit Path without Draft", () => {
        const expected = {
            type: "edit",
            recordID: "record-id",
        };
        const result = getParamsFromPath(`/post/editdiscussion/${expected.recordID}`, "");
        expect(result).toEqual(expected);
    });
    it("getParamsFromPath: Edit Path with Draft", () => {
        const expected = {
            type: "edit",
            draftID: "draft-id",
            recordID: "record-id",
        };
        const result = getParamsFromPath(`/post/editdiscussion/${expected.recordID}/${expected.draftID}`, "");
        expect(result).toEqual(expected);
    });
    it("getParamsFromPath: Groups Path", () => {
        const expected = {
            type: "create",
            postType: "custom-post-type-id",
            parentRecordType: "group",
            parentRecordID: "social-group-id",
        };
        const result = getParamsFromPath(`/post/${expected.postType}/social-groups`, `groupid=social-group-id`);
        expect(result).toEqual(expected);
    });
    it("makePostDraft: Creates a new post draft from form values", () => {
        const input = {
            body: EMPTY_RICH2_BODY,
            format: "rich2",
            name: "test title",
            postFields: {
                "custom-field": "custom-value",
            },
            tags: ["tag-id-1", "tag-id-2"],
            pinLocation: "category",
            categoryID: "category-id",
            postTypeID: "post-type-id",
        } as unknown as MakePostDraftParams;

        const expected = {
            attributes: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                draftMeta: {
                    categoryID: "category-id",
                    pinLocation: "category",
                    postFields: {
                        "custom-field": "custom-value",
                    },
                    postTypeID: "post-type-id",
                    tags: ["tag-id-1", "tag-id-2"],
                    name: "test title",
                },
                draftType: "discussion",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "discussion",
        };
        const result = makePostDraft(input);
        expect(result).toMatchObject(expected);
    });
    it("makeCommentDraft: Creates a new parent comment draft", () => {
        const input = {
            parentRecordID: "parent-record-id",
            parentRecordType: "discussion",
            body: EMPTY_RICH2_BODY,
        } as unknown as MakeCommentDraftParams;

        const expected = {
            attributes: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                draftMeta: {
                    format: "rich2",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        };
        const result = makeCommentDraft(input);
        expect(result).toMatchObject(expected);
    });
    it("makeCommentDraft: Creates a new child comment draft", () => {
        const input = {
            parentRecordID: "parent-record-id",
            parentRecordType: "discussion",
            body: EMPTY_RICH2_BODY,
            commentParentID: 1984,
            commentPath: "1448.5287.1984",
        } as unknown as MakeCommentDraftParams;

        const expected = {
            attributes: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                draftMeta: {
                    format: "rich2",
                    commentParentID: 1984,
                    commentPath: "1448.5287.1984",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        };
        const result = makeCommentDraft(input);
        expect(result).toMatchObject(expected);
    });
    it("makeCommentDraftProps: Create props from a parent comment draft", () => {
        const draft = {
            attributes: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                draftMeta: {
                    format: "rich2",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        } as any;

        const expected = {
            draft: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                dateUpdated: "2025-02-15T00:00:00.000Z",
                draftID: 125,
                format: "rich2",
            },
            draftLastSaved: new Date("2025-02-15T00:00:00.000Z"),
        };
        const result = makeCommentDraftProps(125, draft);
        expect(result).toMatchObject(expected);
    });
    it("makeCommentDraftProps: Create props from a child comment draft", () => {
        const draft = {
            attributes: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                draftMeta: {
                    format: "rich2",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        } as any;

        const expected = {
            draft: {
                body: '[{"type":"p","children":[{"text":""}]}]',
                dateUpdated: "2025-02-15T00:00:00.000Z",
                draftID: 125,
                format: "rich2",
            },
            draftLastSaved: new Date("2025-02-15T00:00:00.000Z"),
        };
        const result = makeCommentDraftProps(125, draft);
        expect(result).toMatchObject(expected);
    });
});
