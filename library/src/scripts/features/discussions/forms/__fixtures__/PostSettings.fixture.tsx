/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { PostTypeFixture } from "@dashboard/postTypes/__fixtures__/PostTypeFixture";
import { PostFieldMap } from "@library/features/discussions/forms/PostSettings.types";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";

export class PostSettingsFixture {
    public static getMockPostFieldMap(
        currentFields: PostField[] = [],
        targetFields: PostField[] = [],
    ): Record<PostFieldMap["currentField"], PostFieldMap> {
        // If no fields are provided, create default maps
        if (currentFields.length === 0 && targetFields.length === 0) {
            return {
                "field-1": {
                    currentField: "field-1",
                    targetField: "field-1-target",
                    currentFieldValue: "Current Value",
                    targetFieldValue: "Target Value",
                },
                "field-2": {
                    currentField: "field-2",
                    targetField: "field-2-target",
                    currentFieldValue: "Another Current Value",
                    targetFieldValue: "Another Target Value",
                },
            };
        }

        // Create map based on provided fields
        return currentFields.reduce((acc, currentField, index) => {
            const targetField = targetFields[index] || targetFields[0] || null;
            return {
                ...acc,
                [currentField.postFieldID]: {
                    currentField: currentField.postFieldID,
                    targetField: targetField?.postFieldID || "",
                    currentFieldValue: `Value for ${currentField.label}`,
                    targetFieldValue: targetField ? `Value for ${targetField.label}` : "",
                },
            };
        }, {});
    }

    public static getMockPostFields(count = 2): PostField[] {
        return Array.from({ length: count }, (_, index) => ({
            postFieldID: `field-${index + 1}`,
            postTypeIDs: ["mock-post-type"],
            name: `Field ${index + 1}`,
            label: `Field ${index + 1} Label`,
            description: `Field ${index + 1} Description`,
            dataType: CreatableFieldDataType.TEXT,
            formType: CreatableFieldFormType.TEXT,
            visibility: CreatableFieldVisibility.PUBLIC,
            isRequired: index === 0, // First field is required
            isActive: true,
            sort: index + 1,
            isExcluded: false,
            labels: {},
            descriptions: {},
            dateInserted: new Date().toISOString(),
            dateUpdated: new Date().toISOString(),
            insertUserID: 1,
            updateUserID: 1,
        }));
    }

    public static getMockDiscussionWithPostMeta(): IDiscussion {
        const discussion = { ...DiscussionFixture.mockDiscussion };
        return {
            ...discussion,
            postTypeID: "discussion",
            postMeta: {
                "field-1": "Value for Field 1",
                "field-2": "Value for Field 2",
            },
        };
    }

    public static getMockPostTypes(count = 2): PostType[] {
        return Array.from({ length: count }, (_, index) => {
            const isDiscussion = index === 0;
            const postTypeID = isDiscussion ? "discussion" : `mock-post-type-${index}`;
            const postFields = this.getMockPostFields(index + 1);

            return {
                ...PostTypeFixture.getMockPostType(),
                postTypeID,
                name: isDiscussion ? "Discussion" : `Mock Post Type ${index}`,
                isOriginal: isDiscussion,
                postFields,
                postFieldIDs: postFields.map((field) => field.postFieldID),
            };
        });
    }
}
