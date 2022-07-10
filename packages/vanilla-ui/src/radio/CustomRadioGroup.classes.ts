/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { srOnlyMixin } from "../utils";

export function customRadioGroupClasses() {
    const input = css({
        ...srOnlyMixin(),
    });
    const accessibleDescription = css({
        ...srOnlyMixin(),
    });

    return { input, accessibleDescription };
}
