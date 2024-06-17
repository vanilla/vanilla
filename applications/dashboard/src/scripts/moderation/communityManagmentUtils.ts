/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComboBoxOption } from "@library/features/search/ISearchBarProps";

export const isHtmlEmpty = (noteHtml: string): boolean => {
    if (!noteHtml) {
        return true;
    }
    const parser = new DOMParser();
    const htmlContent = parser.parseFromString(noteHtml, "text/html");
    return !htmlContent.documentElement.textContent;
};

export const userLookup = {
    searchUrl: "/users/by-names?name=%s*&limit=10",
    labelKey: "name",
    valueKey: "userID",
    singleUrl: "/users/%s",
    processOptions: (options: IComboBoxOption[]) => {
        return options.map((option) => {
            return {
                ...option,
                data: {
                    ...option.data,
                    icon: option.data,
                },
            };
        });
    },
    userIconPath: "icon",
};

export const roleLookUp = {
    searchUrl: "/roles",
    singleUrl: "/roles/%s",
    valueKey: "roleID",
    labelKey: "name",
};

export const categoryLookup = {
    searchUrl: "/categories/search?query=%s&limit=30",
    singleUrl: "/categories/%s",
    valueKey: "categoryID",
    labelKey: "name",
};
