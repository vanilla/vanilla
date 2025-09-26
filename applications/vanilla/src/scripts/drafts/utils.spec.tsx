/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import {
    getParamsFromPath,
    groupDraftsByDateScheduled,
    makeCommentDraft,
    MakeCommentDraftParams,
    makeCommentDraftProps,
    makePostDraft,
    MakePostDraftParams,
    mapDraftToPostFormValues,
} from "@vanilla/addon-vanilla/drafts/utils";
import { mockDrafts } from "@vanilla/addon-vanilla/drafts/Drafts.fixtures";
import { DraftStatus } from "@vanilla/addon-vanilla/drafts/types";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";

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

    const postDraftInput = {
        body: EMPTY_RICH2_BODY,
        format: "rich2",
        name: "test title",
        postMeta: {
            "custom-field": "custom-value",
        },
        tagIDs: [1, 2],
        pinLocation: "category",
        categoryID: 99,
        postTypeID: "post-type-id",
    } as MakePostDraftParams;

    const postDraftExpected = {
        attributes: {
            body: '[{"type":"p","children":[{"text":""}]}]',
            draftMeta: {
                categoryID: 99,
                pinLocation: "category",
                postMeta: {
                    "custom-field": "custom-value",
                },
                postTypeID: "post-type-id",
                tagIDs: [1, 2],
                name: "test title",
            },
            draftType: "discussion",
            format: "rich2",
            lastSaved: "2025-02-15T00:00:00.000Z",
        },
        recordType: "discussion",
    };
    it("makePostDraft: Creates a new post draft from form values", () => {
        const result = makePostDraft(postDraftInput);
        expect(result).toMatchObject(postDraftExpected);
    });

    // we send recordID when we are editing existing discussion and dateScheduled/draftStatus for scheduling/cancelling schedule
    it("makePostDraft: Additional params recordID, draftStatus and dateScheduled are resolved", () => {
        const scheduleAndRecordIDParams = {
            draftStatus: DraftStatus.SCHEDULED,
            dateScheduled: "2025-02-15T00:00:00.000Z",
            recordID: "1",
        };
        const result = makePostDraft({
            ...postDraftInput,
            ...scheduleAndRecordIDParams,
        });
        expect(result).toMatchObject({ ...postDraftExpected, ...scheduleAndRecordIDParams });
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

    it("groupDraftsByDateScheduled: Should return draft groups, by scheduled date", () => {
        const draftsByDate = groupDraftsByDateScheduled(mockDrafts, "en-EN", {
            year: "numeric",
            month: "numeric",
            day: "numeric",
            timeZone: "America/New_York",
        });
        // 3 groups by dates
        expect(Object.keys(draftsByDate).length).toBe(3);
        expect(Object.keys(draftsByDate)).toStrictEqual(["1/22/2025", "1/21/2025", "1/20/2025"]);
    });

    it("mapDraftToPostFormValues: Maps new draft format to post form values", () => {
        const draft = {
            draftID: 370,
            recordType: "discussion",
            parentRecordType: "category",
            parentRecordID: 442,
            attributes: {
                body: '[{"type":"p","children":[{"text":"Draft test here"}]}]',
                format: "rich2",
                draftType: "discussion",
                draftMeta: {
                    name: "Testing Drafts",
                    tags: [],
                    pinLocation: "none",
                    pinned: false,
                    categoryID: 20033,
                    postTypeID: "question",
                },
                lastSaved: "2025-04-14T18:18:43.069Z",
                name: "Vanilla Testing Drafts",
            },
            insertUserID: 25,
            dateInserted: "2025-04-14T18:18:38+00:00",
            updateUserID: 25,
            dateUpdated: "2025-04-14T18:18:43+00:00",
        } as DraftsApi.PostParams;

        const expected = {
            name: "Testing Drafts",
            body: [
                {
                    type: "p",
                    children: [
                        {
                            text: "Draft test here",
                        },
                    ],
                },
            ],
            format: "rich2",
            tagIDs: [],
            pinLocation: "none",
            pinned: false,
            categoryID: 20033,
            postTypeID: "question",
        };

        const result = mapDraftToPostFormValues(draft);
        expect(result).toMatchObject(expected);
    });

    it("mapDraftToPostFormValues: Converts legacy draft", () => {
        const legacyDraft = {
            draftID: 117,
            recordType: "discussion",
            parentRecordType: "category",
            parentRecordID: 85,
            attributes: {
                type: "Discussion",
                name: "test- draft 003",
                format: "Rich2",
                body: '[{"type":"p","children":[{"text":"This is rich 2 content"}]},{"type":"p","children":[{"text":""}]}]',
                announce: "1",
                tags: "",
            },
            insertUserID: 7,
            dateInserted: "2025-04-03T07:28:00+00:00",
            updateUserID: 7,
            dateUpdated: "2025-04-14T18:14:55+00:00",
        } as DraftsApi.PostParams;

        const expected = {
            name: "test- draft 003",
            body: [
                { type: "p", children: [{ text: "This is rich 2 content" }] },
                { type: "p", children: [{ text: "" }] },
            ],
            format: "rich2",
            pinLocation: "category",
            pinned: false,
            categoryID: 85,
            postTypeID: "discussion",
        };

        const result = mapDraftToPostFormValues(legacyDraft);
        expect(result).toMatchObject(expected);
    });
});
