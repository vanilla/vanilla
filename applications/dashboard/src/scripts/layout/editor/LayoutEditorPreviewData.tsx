/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IReaction } from "@dashboard/@types/api/reaction";
import { IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { IUserFragment } from "@library/@types/api/users";
import { ICategoryItem } from "@library/categoriesWidget/CategoryItem";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { ITag } from "@library/features/tags/TagsReducer";
import { IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { ILeader } from "@library/leaderboardWidget/LeaderboardWidget";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { IAddPost, PostTypes } from "@library/newPostMenu/NewPostMenu";
import { NewPostMenuPreview } from "@library/newPostMenu/NewPostMenu.preview";
import { ISiteTotalApiCount, ISiteTotalCount } from "@library/siteTotals/SiteTotals.variables";
import { STORY_LEADERS, STORY_USER } from "@library/storybook/storyData";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { getMeta, siteUrl } from "@library/utility/appUtils";
import { uuidv4 } from "@vanilla/utils";
import random from "lodash-es/random";
import userPhotoUrl from "./icons/userphoto.svg";
import { CreatableFieldDataType, CreatableFieldVisibility } from "@dashboard/userProfiles/types/UserProfiles.types";
import type { ProfileFieldProp } from "@vanilla/addon-vanilla/posts/PostMetaAsset";
import set from "lodash-es/set";

type LayoutEditorPreviewExternallyRegisteredData = {
    widgetCatalog?: IWidgetCatalog;
    externalData?: { [registererName: string]: { registererData: any[]; path: string } };
};

export class LayoutEditorPreviewData {
    public static USER_GEN_TEXT = "This content is generated by users on the site. You can't update it here.";

    /**
     * Stores optional data (from plugins etc)
     */
    public static externallyRegisteredData: LayoutEditorPreviewExternallyRegisteredData = {};

    /**
     * Adds optional data (from plugins etc)
     */
    public static addExternalData(
        widgetCatalog?: LayoutEditorPreviewExternallyRegisteredData["widgetCatalog"],
        externalData?: LayoutEditorPreviewExternallyRegisteredData["externalData"],
    ): void {
        this.externallyRegisteredData = {
            ...this.externallyRegisteredData,
            ...(widgetCatalog && { widgetCatalog: widgetCatalog }),
            externalData: {
                ...this.externallyRegisteredData.externalData,
                ...(externalData ?? {}),
            },
        };
    }

    /**
     * Return some basic static user data in userfragment format.
     */
    public static user(): IUserFragment {
        return {
            userID: 99999999,
            name: "Liza Malzem",
            photoUrl: userPhotoUrl,
            title: "Product Manager",
            dateLastActive: "2016-07-25 17:51:15",
        };
    }

    /**
     * Return some subcommunities data as an array.
     */
    public static subcommunities(options?: { fallbackImage?: string; fallbackIcon?: string }): IHomeWidgetItemProps[] {
        return [
            {
                description: this.USER_GEN_TEXT,
                iconUrl: options?.fallbackIcon ? options?.fallbackIcon : undefined,
                imageUrl: options?.fallbackImage,
                name: "Title",
                to: "#",
            },
            {
                description: this.USER_GEN_TEXT,
                iconUrl: options?.fallbackIcon ? options?.fallbackIcon : undefined,
                imageUrl: options?.fallbackImage,
                name: "Title",
                to: "#",
            },
            {
                description: this.USER_GEN_TEXT,
                iconUrl: options?.fallbackIcon ? options?.fallbackIcon : undefined,
                imageUrl: options?.fallbackImage,
                name: "Title",
                to: "#",
            },
        ];
    }

    /**
     * Return some category data as an array.
     */
    public static categories(
        itemsNumber: number = 6,
        options?: { fallbackImage?: string; fallbackIcon?: string },
    ): ICategoryItem[] {
        let categories: ICategoryItem[] = [];

        for (let i = 0; i < itemsNumber; i++) {
            categories.push({
                counts: [
                    { count: 99000, countAll: 99000, labelCode: "Items" },
                    { count: 99, countAll: 99, labelCode: "Other Items" },
                ],
                description: this.USER_GEN_TEXT,
                iconUrl: options?.fallbackIcon ? options?.fallbackIcon : undefined,
                imageUrl: options?.fallbackImage,
                name: `Category ${i + 1}`,
                to: "#",
                depth: 1,
                displayAs: "discussions",
                categoryID: i,
                lastPost: {},
                children: [
                    {
                        counts: [
                            { count: 99000, countAll: 99000, labelCode: "Items" },
                            { count: 99, countAll: 99, labelCode: "Other Items" },
                        ],
                        description: this.USER_GEN_TEXT,
                        iconUrl: options?.fallbackIcon ? options?.fallbackIcon : undefined,
                        imageUrl: options?.fallbackImage,
                        name: `Child Category ${i + 1}`,
                        to: "#",
                        depth: 1,
                        displayAs: "discussions",
                        categoryID: i + 90,
                        lastPost: {},
                        children: [],
                    },
                ],
            });
        }

        return categories;
    }

    public static attachments(): IAttachment[] {
        return [
            {
                attachmentID: 1,
                attachmentType: "salesforce-lead",
                recordType: "discussion",
                recordID: `${9999999}`,
                state: "Working - Contacted",
                sourceID: `${123456}`,
                sourceUrl: "#",
                insertUser: this.users()[0],
                dateInserted: "2020-10-06T15:30:44+00:00",
                metadata: [
                    {
                        labelCode: "Name",
                        value: this.USER_GEN_TEXT,
                    },
                    {
                        labelCode: "Title",
                        value: this.USER_GEN_TEXT,
                    },
                    {
                        labelCode: "Company",
                        value: this.USER_GEN_TEXT,
                    },
                    {
                        labelCode: "Favourite Color",
                        value: this.USER_GEN_TEXT,
                    },
                    {
                        labelCode: "More Information",
                        value: this.USER_GEN_TEXT,
                    },
                    {
                        labelCode: "Subcontractor",
                        value: this.USER_GEN_TEXT,
                    },
                ],
            },
        ];
    }

    public static reactions(): IReaction[] {
        return [
            {
                tagID: 1,
                urlcode: "Promote",
                name: "Promote",
                class: "Positive",
                hasReacted: false,
                reactionValue: 5,
                count: 0,
            },
            {
                tagID: 2,
                urlcode: "Disagree",
                name: "Disagree",
                class: "Negative",
                hasReacted: false,
                reactionValue: 0,
                count: 3,
            },
            {
                tagID: 3,
                urlcode: "Agree",
                name: "Agree",
                class: "Positive",
                hasReacted: true,
                reactionValue: 1,
                count: 2,
            },
            {
                tagID: 4,
                urlcode: "Like",
                name: "Like",
                class: "Positive",
                hasReacted: false,
                reactionValue: 1,
                count: 0,
            },
            {
                tagID: 5,
                urlcode: "LOL",
                name: "LOL",
                class: "Positive",
                hasReacted: false,
                reactionValue: 0,
                count: 7,
            },
        ];
    }

    public static discussion(): IDiscussion {
        const user = this.randomUser();
        const suggestions = this.suggestions();
        return {
            discussionID: 9999999,
            type: "discussion",
            name: "Discussion Title",
            body: "This content is generated by users on the site. You can't update it here.<br><br>This content is generated by users on the site. You can't update it here. This content is generated by users on the site. You can't update it here.<br><br>This content is generated by users on the site. You can't update it here.",
            url: siteUrl("/#"),
            canonicalUrl: "#",
            dateInserted: "2020-10-06T15:30:44+00:00",
            insertUserID: user.userID,
            insertUser: user,
            lastUser: user,
            dateUpdated: "2020-10-06T15:30:44+00:00",
            dateLastComment: "2020-10-06T15:30:44+00:00",
            pinned: false,
            closed: false,
            score: 0,
            countViews: 999,
            countComments: 9999,
            categoryID: 1111111111111111,
            category: {
                name: "Category 1",
                url: siteUrl("/#"),
                categoryID: 1111111111111111,
            },
            reactions: this.reactions(),
            attachments: this.attachments(),
            statusID: 1,
            ...(suggestions && { suggestions, showSuggestions: true }),
        };
    }

    public static comments(count = 5): IComment[] {
        const user1 = STORY_LEADERS[1].user;
        const user2 = STORY_LEADERS[2].user;
        const user3 = STORY_LEADERS[3].user;
        const user4 = STORY_LEADERS[4].user;

        const fakeComments: IComment[] = [
            {
                commentID: 999999,
                parentRecordType: "discussion",
                categoryID: 1000,
                parentRecordID: 999999,
                insertUser: user1,
                insertUserID: user1.userID,
                dateInserted: "2020-10-06T15:30:44+00:00",
                dateUpdated: "2020-10-06T15:30:44+00:00",
                score: 999,
                url: "https://vanillaforums.com/discussion/comment/999999#Comment_999999",
                attributes: {},
                body: this.USER_GEN_TEXT,
                name: this.USER_GEN_TEXT,
                reactions: this.reactions(),
            },
            {
                commentID: 999999,
                parentRecordType: "discussion",
                categoryID: 1000,
                parentRecordID: 999999,
                insertUser: user2,
                insertUserID: user2.userID,
                dateInserted: "2020-10-06T15:30:44+00:00",
                dateUpdated: "2020-10-06T15:30:44+00:00",
                score: 999,
                url: "https://vanillaforums.com/discussion/comment/999999#Comment_999999",
                attributes: {},
                body: this.USER_GEN_TEXT,
                name: this.USER_GEN_TEXT,
                reactions: this.reactions(),
            },
            {
                commentID: 999999,
                parentRecordType: "discussion",
                categoryID: 1000,
                parentRecordID: 999999,
                insertUser: user3,
                insertUserID: user3.userID,
                dateInserted: "2020-10-06T15:30:44+00:00",
                dateUpdated: "2020-10-06T15:30:44+00:00",
                score: 999,
                url: "https://vanillaforums.com/discussion/comment/999999#Comment_999999",
                attributes: {},
                body: this.USER_GEN_TEXT,
                name: this.USER_GEN_TEXT,
                reactions: this.reactions(),
            },
            {
                commentID: 999999,
                parentRecordType: "discussion",
                categoryID: 1000,
                parentRecordID: 999999,
                insertUser: user4,
                insertUserID: user4.userID,
                dateInserted: "2020-10-06T15:30:44+00:00",
                dateUpdated: "2020-10-06T15:30:44+00:00",
                score: 999,
                url: "https://vanillaforums.com/discussion/comment/999999#Comment_999999",
                attributes: {},
                body: this.USER_GEN_TEXT,
                name: this.USER_GEN_TEXT,
                reactions: this.reactions(),
            },
            {
                commentID: 999999,
                parentRecordType: "discussion",
                categoryID: 1000,
                parentRecordID: 999999,
                insertUser: user1,
                insertUserID: user1.userID,
                dateInserted: "2020-10-06T15:30:44+00:00",
                dateUpdated: "2020-10-06T15:30:44+00:00",
                score: 999,
                url: "https://vanillaforums.com/discussion/comment/999999#Comment_999999",
                attributes: {},
                body: this.USER_GEN_TEXT,
                name: this.USER_GEN_TEXT,
                reactions: this.reactions(),
            },
        ];
        let comments: IComment[] = [];

        for (let i = 0; i < count; i++) {
            comments.push({
                ...fakeComments[i % fakeComments.length],
                commentID: random(1, 999999),
            });
        }

        const hasExternalData =
            LayoutEditorPreviewData.externallyRegisteredData?.externalData &&
            Object.values(LayoutEditorPreviewData.externallyRegisteredData.externalData).length > 0;

        if (hasExternalData) {
            const externalData = LayoutEditorPreviewData.externallyRegisteredData.externalData;
            Object.keys(externalData ?? {}).forEach((dataKey) => {
                if (externalData?.[dataKey]) {
                    comments.forEach((comment) => {
                        set(comment, externalData?.[dataKey].path, externalData?.[dataKey].registererData);
                    });
                }
            });
        }

        return comments;
    }

    public static paging(limit: number, total = 999): ILinkPages {
        return {
            nextURL: "#",
            prevURL: "#",
            currentPage: 1,
            limit,
            total,
        };
    }

    public static suggestions(): ISuggestedAnswer[] | undefined {
        const showSuggestions = getMeta("answerSuggestionsEnabled", false);
        if (showSuggestions) {
            return Array(3)
                .fill(0)
                .map((_, idx) => ({
                    aiSuggestionID: 1,
                    format: "Vanilla",
                    type: "discussion",
                    documentID: idx + 1,
                    url: siteUrl("/#"),
                    title: "Suggested Discussion Title",
                    summary:
                        "This is an AI generated summary from the referenced discussion post that might answer the question. The summary is created in a way for it to be used as an accepted answer.",
                    hidden: false,
                }));
        }

        return undefined;
    }

    /**
     * Return mock discussions to be consumed by layout widgets.
     */
    public static discussions(count = 3, includeUnread?: boolean): IDiscussion[] {
        const fakeDiscussions: IDiscussion[] = [
            {
                discussionID: 9999999,
                type: "discussion",
                name: "Discussion Title",
                excerpt: this.USER_GEN_TEXT,
                url: siteUrl("/#"),
                canonicalUrl: "#",
                dateInserted: "2020-10-06T15:30:44+00:00",
                insertUserID: STORY_USER.userID,
                insertUser: STORY_USER,
                lastUser: STORY_USER,
                dateUpdated: "2020-10-06T15:30:44+00:00",
                dateLastComment: "2020-10-06T15:30:44+00:00",
                pinned: false,
                closed: false,
                score: 0,
                countViews: 999,
                countComments: 9999,
                categoryID: 1111111111111111,
                category: {
                    name: "Category 1",
                    url: siteUrl("/#"),
                    categoryID: 1111111111111111,
                },
                statusID: 1,
            },
            {
                discussionID: 99999998,
                type: "discussion",
                name: "Discussion Title",
                excerpt: this.USER_GEN_TEXT,
                url: siteUrl("/#"),
                canonicalUrl: "#",
                dateInserted: "2020-10-06T15:30:44+00:00",
                insertUserID: STORY_USER.userID,
                insertUser: STORY_USER,
                lastUser: STORY_USER,
                dateUpdated: "2020-10-06T15:30:44+00:00",
                dateLastComment: "2020-10-06T15:30:44+00:00",
                pinned: false,
                closed: false,
                score: 0,
                countViews: 999,
                countComments: 9999,
                countUnread: includeUnread ? 99 : undefined,
                categoryID: 22222222222222,
                category: {
                    name: "Category 2",
                    url: siteUrl("/#"),
                    categoryID: 22222222222222,
                },
                statusID: 1,
            },
            {
                discussionID: 99999991,
                type: "discussion",
                name: "Discussion Title",
                excerpt: this.USER_GEN_TEXT,
                url: siteUrl("/#"),
                canonicalUrl: "#",
                dateInserted: "2020-10-06T15:30:44+00:00",
                insertUserID: STORY_USER.userID,
                insertUser: STORY_USER,
                lastUser: STORY_USER,
                dateUpdated: "2020-10-06T15:30:44+00:00",
                dateLastComment: "2020-10-06T15:30:44+00:00",
                pinned: false,
                closed: false,
                score: 0,
                countViews: 999,
                countComments: 9999,
                categoryID: 33333333333,
                category: {
                    name: "Category 3",
                    url: siteUrl("/#"),
                    categoryID: 33333333333,
                },
                statusID: 1,
                tags: [{ tagID: 1111111, name: "User Tag", urlcode: "#" }],
            },
        ];

        let discussions: IDiscussion[] = [];

        for (let i = 0; i < count; i++) {
            discussions.push({
                ...fakeDiscussions[i % fakeDiscussions.length],
                discussionID: uuidv4(),
            });
        }

        return discussions;
    }

    /*
     * Return mock data to be consumed by the Leaderboard Widget.
     * @returns ILeader[]
     */
    public static leaders(): ILeader[] {
        return STORY_LEADERS.slice(0, 6);
    }

    /*
     * Return mock data to be consumed by the Online Widget.
     */
    public static users(): IUserFragment[] {
        return STORY_LEADERS.slice(0, 6).map(({ user }) => user);
    }

    /**
     * Get a random mock user.
     */
    public static randomUser(): IUserFragment {
        return STORY_LEADERS[random(0, 5)].user;
    }

    /**
     * Returns a single tag
     */
    static createTag(name: string, tagID: number, overrides?: any): ITag {
        return {
            tagID,
            name,
            urlcode: name,
            ...overrides,
        };
    }

    /**
     * Returns mock tags based off the tag names specified,
     * defaults to "support", "product", "success", "sales", "user-experience", "api"
     */
    public static tags(tagNames?: string[]): ITag[] {
        return (tagNames ?? ["Support", "Product", "Success", "Sales", "User Experience", "API"]).map(
            (name: string, index: number) => this.createTag(name, index),
        );
    }

    /**
     * Return mock data to be consumed by the New Post Widget.
     * @returns IAddPost[] and post type keys in an object
     */
    public static getPostTypes(options: React.ComponentProps<typeof NewPostMenuPreview>): {
        options: IAddPost[];
        types: string[];
    } {
        const asOwnButtonsList = options.asOwnButtons ?? [];
        const customLabels = options.customLabels ?? [];
        const excludedButtons = options.excludedButtons ?? [];

        const allPostTypes = {
            discussion: {
                label: customLabels["discussion"] ?? "New Discussion",
                action: "#",
                type: PostTypes.LINK,
                id: "create-discussion",
                icon: "create-discussion",
                asOwnButton: !!asOwnButtonsList.find((postType) => postType === "discussion"),
            },
            question: {
                label: customLabels["question"] ?? "Ask a Question",
                action: "#",
                type: PostTypes.LINK,
                id: "ask-a-question",
                icon: "create-question",
                asOwnButton: !!asOwnButtonsList.find((postType) => postType === "question"),
            },
            idea: {
                label: customLabels["idea"] ?? "New Idea",
                action: "#",
                type: PostTypes.LINK,
                id: "create-idea",
                icon: "create-idea",
                asOwnButton: !!asOwnButtonsList.find((postType) => postType === "idea"),
            },
            poll: {
                label: customLabels["poll"] ?? "New Poll",
                action: "#",
                type: PostTypes.LINK,
                id: "create-poll",
                icon: "create-poll",
                asOwnButton: !!asOwnButtonsList.find((postType) => postType === "poll"),
            },
            event: {
                label: customLabels["event"] ?? "New Event",
                action: "#",
                type: PostTypes.LINK,
                id: "create-event",
                icon: "create-event",
                asOwnButton: !!asOwnButtonsList.find((postType) => postType === "event"),
            },
        };

        let postTypes = {
            discussion: allPostTypes["discussion"],
        };

        if (self.externallyRegisteredData?.widgetCatalog) {
            const widgetCatalog = self.externallyRegisteredData.widgetCatalog;
            const typeOptions =
                widgetCatalog["react.newpost"].schema.properties.excludedButtons["x-control"].choices.staticOptions;
            const possibleTypesFromSchema = Object.keys(typeOptions ?? {});
            possibleTypesFromSchema.forEach((type) => {
                if (allPostTypes.hasOwnProperty(type)) {
                    postTypes[type] = allPostTypes[type];
                }
            });
        } else {
            postTypes = allPostTypes;
        }

        excludedButtons.forEach((button) => {
            delete postTypes[button];
        });

        return { options: Object.values(postTypes), types: Object.keys(postTypes) };
    }

    /**
     * Return mock data to be consumed by the Site Totals Widget.
     * @returns ISiteTotalCount[]
     */
    public static getSiteTotals(counts: ISiteTotalApiCount[] = []): ISiteTotalCount[] {
        const iconMap = {
            accepted: "meta-answered",
            article: "meta-article",
            category: "meta-categories",
            comment: "meta-discussions",
            discussion: "reaction-comments",
            event: "meta-events",
            group: "meta-groups",
            knowledgeBase: "meta-knowledge-bases",
            onlineUser: "whos-online",
            onlineMember: "meta-users",
            post: "meta-posts",
            question: "meta-questions",
            user: "meta-users",
        };
        return counts
            .filter((item) => !item.isHidden)
            .map((item) => ({
                ...item,
                count: 99999,
                isCalculating: false,
                isFiltered: false,
                iconName: iconMap[item.recordType],
            }));
    }

    public static getBreadCrumbs(): ICrumb[] {
        return [
            {
                name: "Home",
                url: siteUrl("/#"),
            },
            {
                name: "Parent Category",
                url: siteUrl("/#"),
            },
            {
                name: "Child Category",
                url: siteUrl("/#"),
            },
        ];
    }

    public static postFields(): ProfileFieldProp[] {
        return [
            {
                dataType: CreatableFieldDataType.TEXT,
                postFieldID: "1",
                label: "Text Field",
                value: "This content is generated by users on the site.",
                description: "This description is set on the post fields configuration page.",
                visibility: CreatableFieldVisibility.PUBLIC,
            },
            {
                dataType: CreatableFieldDataType.NUMBER,
                postFieldID: "2",
                label: "Number Field",
                value: "This content is generated by users on the site.",
                description: "This description is set on the post fields configuration page.",
                visibility: CreatableFieldVisibility.PUBLIC,
            },
            {
                dataType: CreatableFieldDataType.BOOLEAN,
                postFieldID: "3",
                label: "Checkbox Field",
                value: true,
                description: "This description is set on the post fields configuration page.",
                visibility: CreatableFieldVisibility.PUBLIC,
            },
            {
                dataType: CreatableFieldDataType.DATE,
                postFieldID: "4",
                label: "Date Field",
                value: "2020-10-06T15:30:44+00:00",
                description: "This description is set on the post fields configuration page.",
                visibility: CreatableFieldVisibility.PUBLIC,
            },
        ];
    }
}
