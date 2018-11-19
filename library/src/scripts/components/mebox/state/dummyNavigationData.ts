/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { IVanillaHeaderNavProps } from "../pieces/VanillaHeaderNav";

export const dummyNavigationData: IVanillaHeaderNavProps = {
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
        {
            to: "/kb",
            name: "Knowledge Base",
        },
    ],
};

export const dummyGuestNavigationData: IVanillaHeaderNavProps = {
    children: [
        {
            to: `/entry/signin?target=${window.location.pathname}`,
            name: "Sign In",
        },
    ],
};
