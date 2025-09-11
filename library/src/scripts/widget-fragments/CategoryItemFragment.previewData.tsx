import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import CategoryItem from "@vanilla/injectables/CategoryItemFragment";
import { uuidv4 } from "@vanilla/utils";

const categoryItem: CategoryItem.CategoryItem = {
    name: "Category 1",
    iconUrl: undefined,
    url: "#",
    to: "#",
    displayAs: "discussions",
    description: "This is a category description",
    bannerImageUrl: undefined,
    parentCategoryID: 0,
    customPermissions: false,
    isArchived: false,
    urlcode: "category-1",
    countCategories: 9,
    countDiscussions: 99,
    countComments: 99,
    countAllComments: 999,
    countAllDiscussions: 999,
    countFollowers: 99,
    preferences: {
        "preferences.followed": false,
        "preferences.email.digest": false,
    },
    followed: false,
    depth: 0,
    dateInserted: "2020-10-06T15:30:44+00:00",
    categoryID: 1,
    allowedDiscussionTypes: ["discussion"],
    counts: [
        {
            labelCode: "discussions",
            count: 99,
            countAll: 999,
        },
        {
            labelCode: "comments",
            count: 99,
            countAll: 999,
        },
        {
            labelCode: "posts",
            count: 198,
            countAll: 1998,
        },
        {
            labelCode: "followers",
            count: 99,
        },
    ],
    lastPost: {
        name: "Last Post",
        url: "#",
        dateInserted: "2022-01-01T15:30:44+00:00",
        discussionID: -1,
        insertUserID: -1,
        insertUser: {
            userID: -1,
            name: "System",
            url: "",
            photoUrl: "",
            dateLastActive: "2025-05-05T19:57:16+00:00",
            banned: 0,
            private: false,
        },
    },
};

export const ImageTypeNoneData: IFragmentPreviewData<CategoryItem.Props> = {
    name: "Image Type: None",
    previewDataUUID: uuidv4(),
    description: "A discussions type category with the none image type selected.",
    data: {
        categoryItem: {
            ...categoryItem,
        },
        options: {
            metas: {
                display: {
                    postCount: true,
                    lastPostAuthor: true,
                    lastPostDate: true,
                    followerCount: true,
                },
            },
        },
        imageType: "none",
    },
};

export const ImageTypeIconData: IFragmentPreviewData<CategoryItem.Props> = {
    name: "Image Type: Icon",
    previewDataUUID: uuidv4(),
    description: "A discussions type category with the icon image type selected.",
    data: {
        categoryItem: {
            ...categoryItem,
        },
        options: {
            metas: {
                display: {
                    postCount: true,
                    lastPostAuthor: true,
                    lastPostDate: true,
                    followerCount: true,
                },
            },
        },
        imageType: "icon",
    },
};

export const ImageTypeImageData: IFragmentPreviewData<CategoryItem.Props> = {
    name: "Image Type: Image",
    previewDataUUID: uuidv4(),
    description: "An image type category with the image type selected.",
    data: {
        categoryItem: {
            ...categoryItem,
        },
        options: {
            metas: {
                display: {
                    postCount: true,
                    lastPostAuthor: true,
                    lastPostDate: true,
                    followerCount: true,
                },
            },
        },
        imageType: "image",
    },
};

export const ImageTypeBackgroundData: IFragmentPreviewData<CategoryItem.Props> = {
    name: "Image Type: Background",
    previewDataUUID: uuidv4(),
    description: "A background type category with the background image type selected and minimal metadata.",
    data: {
        categoryItem: {
            ...categoryItem,
        },
        options: {
            metas: {
                asIcons: "icon",
                display: {
                    postCount: true,
                    lastPostDate: true,
                    followerCount: true,
                },
            },
        },
        imageType: "background",
    },
};

const previewData: Array<IFragmentPreviewData<CategoryItem.Props>> = [
    ImageTypeNoneData,
    ImageTypeIconData,
    ImageTypeImageData,
    ImageTypeBackgroundData,
];

export default previewData;
