/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { IHeaderNavigationProps } from "../pieces/HeaderNavigation";

export const dummyNavigationData: IHeaderNavigationProps = {
    children: [
        {
            to: "/discussions",
            name: "Discussions",
        },
        {
            to: "/kb/categories",
            name: "KB Categories",
        },
        {
            to: "/",
            name: "Forum",
        },
    ],
};
