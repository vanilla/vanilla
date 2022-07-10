/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
export class LayoutEditorPreviewData {
    /**
     * Return some basic static user data in userfragment format.
     */
    public static user(): IUserFragment {
        return {
            userID: 999999999999999999,
            name: "Liza Malzem",
            photoUrl: require("!file-loader!./icons/userphoto.svg").default,
            title: "Product Manager",
            dateLastActive: "2016-07-25 17:51:15",
        };
    }

    /**
     * Return some category data as an array.
     */
    public static categories(): IHomeWidgetItemProps[] {
        return [
            {
                counts: [
                    { count: 99000, labelCode: "Items" },
                    { count: 99, labelCode: "Other Items" },
                ],
                description: "This content is generated by users on the site. You can’t update it here.",
                iconUrl: undefined,
                imageUrl: undefined,
                name: "Title",
                to: "#",
            },
            {
                counts: [
                    { count: 99000, labelCode: "Items" },
                    { count: 99, labelCode: "Other Items" },
                ],
                description: "This content is generated by users on the site. You can’t update it here.",
                iconUrl: undefined,
                imageUrl: undefined,
                name: "Title",
                to: "#",
            },
            {
                counts: [
                    { count: 99000, labelCode: "Items" },
                    { count: 99, labelCode: "Other Items" },
                ],
                description: "This content is generated by users on the site. You can’t update it here.",
                iconUrl: undefined,
                imageUrl: undefined,
                name: "Title",
                to: "#",
            },
        ];
    }
}
