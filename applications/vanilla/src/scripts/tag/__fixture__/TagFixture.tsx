/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITag } from "@library/features/tags/TagsReducer";
import { slugify } from "@vanilla/utils";

export class TagFixture {
    public static mockTag: ITag = {
        tagID: 1,
        name: "Mock Tag 1",
        urlcode: "/mock-tag",
        countDiscussions: 10,
    };

    public static getTags(numberOfTags = 1, overrides?: Partial<ITag>): ITag[] {
        return Array.from({ length: numberOfTags }, (_, index) => {
            const name = `Mock Tag ${index + 1}`;
            const urlcode = slugify(name);
            return {
                tagID: index + 1,
                name,
                urlcode,
                countDiscussions: 10,
                ...overrides,
            } as ITag;
        });
    }
}
