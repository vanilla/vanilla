/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";

export const dummyStorybookNavigationData = () => {
    return {
        data: [
            {
                to: "/categories",
                children: t("Home"),
            },
            {
                to: "/categories",
                children: t("Articles"),
            },
            {
                to: "/categories",
                children: t("Latest Discussions"),
            },
        ],
    };
};
