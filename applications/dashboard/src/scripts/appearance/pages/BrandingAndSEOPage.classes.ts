/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { Variables } from "@library/styles/Variables";
import { calc } from "csx";

export const BrandingAndSEOPageClasses = {
    layout: css({
        display: "flex",
        padding: 0,
        maxWidth: "100%",

        "& > section": {
            width: "100%",
            maxWidth: 1024,
            ...Mixins.padding(
                Variables.spacing({
                    bottom: 18,
                    horizontal: 18,
                }),
            ),
            "& > li > div:last-child > label": {
                float: "right",
            },
            "& .input-wrap textarea": {
                fontSize: 14,
            },
        },
    }),
};
