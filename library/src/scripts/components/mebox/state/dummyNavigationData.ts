/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { IVanillaHeaderNavProps } from "../pieces/VanillaHeaderNav";

export const dummyNavigationData = {
    data: [
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
        {
            to: "/kb",
            name: "Knowledge Base",
        },
    ],
};

export const dummyGuestNavigationData = {
    data: [
        {
            to: `/entry/signin?target=${window.location.pathname}`,
            name: "Sign In",
        },
    ],
};
