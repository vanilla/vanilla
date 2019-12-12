/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";

export const dummyStorybookNavigationData = () => {
    return {
        data: [
            {
                to: "/",
                children: t("Link 1"),
            },
            {
                to: "/",
                children: t("Link 2"),
            },
            {
                to: "/",
                children: t("Latest Discussions"),
            },
        ],
    };
};
