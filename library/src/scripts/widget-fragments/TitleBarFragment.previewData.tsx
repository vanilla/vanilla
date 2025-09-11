import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import TitleBarFragment from "@library/widget-fragments/TitleBarFragment.injectable";
import { uuidv4 } from "@vanilla/utils";

const previewData: Array<IFragmentPreviewData<TitleBarFragment.Props>> = [
    {
        previewDataUUID: uuidv4(),
        name: "Simple Navigation",
        description: "A with all the basics, including some simple navigation items.",
        data: {
            navigation: {
                items: [
                    {
                        name: "Posts",
                        id: "posts",
                        url: "#posts",
                    },
                    {
                        name: "Categories",
                        id: "categories",
                        url: "#categories",
                    },
                    {
                        name: "Groups",
                        id: "groups",
                        url: "#groups",
                    },
                    {
                        name: "Help",
                        id: "kb",
                        url: "#kb",
                    },
                ],
            },
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Nested Navigation",
        description: "A titlebar with custom navigation items configured.",
        data: {
            navigation: {
                items: [
                    {
                        name: "Posts",
                        id: "posts",
                        url: "#posts",
                    },
                    {
                        name: "Categories",
                        id: "categories",
                        url: "#categories",
                    },
                    {
                        name: "Groups",
                        id: "groups",
                        url: "#groups",
                    },
                    {
                        name: "Help",
                        id: "kb",
                        url: "#kb",
                        children: [
                            {
                                name: "Top Articles",
                                id: "top-articles",
                                url: "#top-articles",
                            },
                            {
                                name: "Guides",
                                id: "Guides",
                                url: "#guides",
                            },
                        ],
                    },
                ],
            },
        },
    },
];

export default previewData;
