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
            name: "Community",
        },
        {
            to: "/categories",
            name: "Categories",
        },
        {
            to: "/kb",
            name: "Help",
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
