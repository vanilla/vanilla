/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerReducer } from "@dashboard/state/reducerRegistry";
import editorReducer from "@rich-editor/state/editorReducer";
import { onReady } from "@dashboard/application";

registerReducer("editor", editorReducer);
