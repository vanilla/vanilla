/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { t } from "@library/utility/appUtils";

export const navigationVariables = () => {
    return {
        en: {
            data: [
                {
                    to: "/kb",
                    children: t("Help Menu", "Learning Center"),
                    permission: "kb.view",
                },
                {
                    to: "/discussions",
                    children: t("Discussions"),
                },
                {
                    to: "/categories",
                    children: t("Innovation Hub"),
                },
            ],
        },
    };
};
