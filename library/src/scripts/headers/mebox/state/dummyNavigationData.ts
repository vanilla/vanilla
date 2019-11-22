/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";

export const dummyNavigationData = {
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
            children: t("Help Menu"),
        },
    ],
};
