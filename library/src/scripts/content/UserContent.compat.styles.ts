/**
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { injectGlobal } from "@emotion/css";
import { userContentMixin } from "@library/content/UserContent.styles";

export const userContentCompatCSS = () => {
    injectGlobal({
        ".userContent.userContent, .UserContent.UserContent": {
            ...userContentMixin(),
        },
    });
};
