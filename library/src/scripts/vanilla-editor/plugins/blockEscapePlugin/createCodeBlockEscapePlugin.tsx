/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { withCodeBlockEscape } from "@library/vanilla-editor/plugins/blockEscapePlugin/withCodeBlockEscape";
import { createPluginFactory } from "@udecode/plate-core";

export const createCodeBlockEscapePlugin = createPluginFactory<{}>({
    key: "codeBlockEscape",
    withOverrides: withCodeBlockEscape,
});
