/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { t } from "@library/utility/appUtils";

export const navigationVariables = () => {
    return {
        fr: {
            data: [
                {
                    to: "/categories",
                    children: t("Categories"),
                },
                {
                    to: "/discussions",
                    children: t("Discussions"),
                },
                {
                    to: "/kb",
                    children: t("Help Menu", "Help"),
                    permission: "kb.view",
                },
            ],
        },
        en: {
            data: [
                {
                    to: "/kb",
                    children: t("Help Menu", "Help"),
                    permission: "kb.view",
                },
                {
                    to: "/discussions",
                    children: t("Discussions"),
                },
                {
                    to: "/categories",
                    children: t("Categories"),
                },
            ],
        },
    };
};
