/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CategoryDisplayAs, ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { slugify } from "@vanilla/utils";

export class CategoryFixture {
    public static mockCategory: ICategory = {
        categoryID: 1,
        name: "Mock Category 1",
        url: "/mock-category",
        description: "mock category description",
        parentCategoryID: null,
        customPermissions: false,
        isArchived: false,
        urlcode: "/",
        displayAs: CategoryDisplayAs.DEFAULT,
        countCategories: 1,
        countDiscussions: 10,
        countComments: 10,
        countAllDiscussions: 10,
        countAllComments: 10,
        followed: false,
        depth: 1,
        children: [],
        dateInserted: new Date("1990-02-20").toUTCString(),
    };

    public static getCategories(numberOfCategories = 1, overrides?: Partial<ICategory>): ICategory[] {
        return Array.from({ length: numberOfCategories }, (_, index) => {
            const name = `Mock Category ${index + 1}`;
            const url = slugify(name);
            return {
                categoryID: index + 1,
                name,
                url,
                description: `Mock Category ${index + 1} description`,
                parentCategoryID: null,
                customPermissions: false,
                isArchived: false,
                urlcode: "/",
                displayAs: CategoryDisplayAs.DEFAULT,
                countCategories: 1,
                countDiscussions: 10,
                countComments: 10,
                countAllDiscussions: 10,
                countAllComments: 10,
                followed: false,
                depth: 1,
                children: [],
                dateInserted: new Date("1990-02-20").toUTCString(),
                ...overrides,
            } as ICategory;
        });
    }
}
