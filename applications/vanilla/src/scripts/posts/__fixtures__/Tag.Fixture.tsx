/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITag } from "@library/features/tags/TagsReducer";

export class TagFixture {
    public static mockTag: ITag = {
        tagID: 0,
        name: "Mock Tag",
        urlcode: "mock-tag",
        parentTagID: null,
        countDiscussions: 0,
        type: "User",
    };

    public static getMockTags(numberOfTags: number, overrides?: Partial<ITag>): ITag[] {
        return Array.from({ length: numberOfTags }, (_, index) => {
            return {
                ...this.mockTag,
                tagID: index,
                name: `Mock Tag ${index}`,
                urlcode: `mock-tag-${index}`,
                ...overrides,
            };
        });
    }
}
