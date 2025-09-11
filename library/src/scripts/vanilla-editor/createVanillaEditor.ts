/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MyEditor } from "@library/vanilla-editor/typescript";
import { VanillaEditorPlugins } from "@library/vanilla-editor/VanillaEditor.plugins";
import { createMyPlateEditor } from "./getMyEditor";

export function createVanillaEditor(options?: { initialValue?: MyEditor; id?: string }) {
    return createMyPlateEditor({
        id: options?.id,
        plugins: VanillaEditorPlugins,
        editor: options?.initialValue,
    });
}
