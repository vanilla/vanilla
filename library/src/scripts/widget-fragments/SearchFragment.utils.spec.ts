/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { convertSearchAPIParamsToURL } from "@library/widget-fragments/SearchFragment.utils";

describe("convertSearchAPIParamsToURL mapping", () => {
    it("query -> query", () => {
        const expected = "query=test";
        const result = convertSearchAPIParamsToURL({
            query: "test",
        });
        expect(result).toBe(expected);
    });
    it("recordType -> domain", () => {
        const expected = "domain=event";
        const result = convertSearchAPIParamsToURL({
            recordTypes: "event",
        });
        expect(result).toBe(expected);
    });
    it("types -> types", () => {
        const withDiscussion = encodeURI("domain=discussion&types[0]=discussion&types[1]=question");
        const withoutDiscussion = encodeURI("domain=event");

        expect.assertions(2);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "discussion",
                types: ["discussion", "question"],
            }),
        ).toBe(withDiscussion);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                types: ["discussion", "question"],
            }),
        ).toBe(withoutDiscussion);
    });
    it("discussionID -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            discussionID: 7,
        });
        expect(result).toBe(expected);
    });
    it("categoryID -> categoryIDs", () => {
        const withDiscussion = encodeURI("domain=discussion&categoryIDs[0]=12&categoryIDs[1]=24");
        const withoutDiscussion = encodeURI("domain=event");

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "discussion",
                categoryID: [12, 24],
            }),
        ).toBe(withDiscussion);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                categoryID: [12, 24],
            }),
        ).toBe(withoutDiscussion);
    });
    it("followedCategories -> followedCategories", () => {
        const withDiscussion = encodeURI("domain=discussion&followedCategories=true");
        const withoutDiscussion = encodeURI("domain=event");

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "discussion",
                followedCategories: true,
            }),
        ).toBe(withDiscussion);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                followedCategories: true,
            }),
        ).toBe(withoutDiscussion);
    });
    it("includeChildCategories -> includeChildCategories", () => {
        const withDiscussion = encodeURI(
            "domain=discussion&categoryIDs[0]=12&categoryIDs[1]=24&includeChildCategories=true",
        );
        const withoutDiscussion = encodeURI("domain=event");

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "discussion",
                categoryID: ["12", "24"],
                includeChildCategories: true,
            }),
        ).toBe(withDiscussion);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                categoryID: ["12", "24"],
                includeChildCategories: true,
            }),
        ).toBe(withoutDiscussion);
    });
    it("includeArchivedCategories -> includeArchivedCategories", () => {
        const withDiscussion = encodeURI("domain=discussion&includeArchivedCategories=true");
        const withoutDiscussion = encodeURI("domain=event");

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "discussion",
                includeArchivedCategories: true,
            }),
        ).toBe(withDiscussion);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                includeArchivedCategories: true,
            }),
        ).toBe(withoutDiscussion);
    });

    it("knowledgeBaseID -> knowledgeBaseOption", () => {
        const withArticle = encodeURI("domain=article&knowledgeBaseOption=42");
        const withoutArticle = encodeURI("domain=event");

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "article",
                knowledgeBaseID: 42,
            }),
        ).toBe(withArticle);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                knowledgeBaseID: 42,
            }),
        ).toBe(withoutArticle);
    });
    it("knowledgeCategoryIDs -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            knowledgeCategoryIDs: 17,
        });
        expect(result).toBe(expected);
    });
    it("name -> name", () => {
        const expected = "name=test";
        const result = convertSearchAPIParamsToURL({
            name: "test",
        });
        expect(result).toBe(expected);
    });
    it("featured -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            featured: true,
        });
        expect(result).toBe(expected);
    });
    it("locale -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            locale: "fr",
        });
        expect(result).toBe(expected);
    });
    it("siteSiteSectionGroup -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            siteSiteSectionGroup: 7,
        });
        expect(result).toBe(expected);
    });
    it("insertUserNames -> authors", () => {
        const withDiscussion = encodeURI("domain=discussion&authors[0][value]=12");
        const withArticle = encodeURI("domain=article&authors[0][value]=12");
        const withoutArticleOrDiscussion = encodeURI("domain=event");

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "article",
                insertUserNames: [12],
            }),
        ).toBe(withArticle);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "discussion",
                insertUserNames: [12],
            }),
        ).toBe(withDiscussion);

        expect(
            convertSearchAPIParamsToURL({
                recordTypes: "event",
                insertUserNames: [12],
            }),
        ).toBe(withoutArticleOrDiscussion);
    });
    it("limit -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            limit: 10,
        });
        expect(result).toBe(expected);
    });
    it("expandBody -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            expandBody: true,
        });
        expect(result).toBe(expected);
    });
    it("expand -> null", () => {
        const expected = "";
        const result = convertSearchAPIParamsToURL({
            expand: true,
        });
        expect(result).toBe(expected);
    });
});
