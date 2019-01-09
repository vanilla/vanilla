/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerReducer } from "@library/state/reducerRegistry";
import editorReducer from "@rich-editor/state/editorReducer";
import "../../scss/editor.scss";

registerReducer("editor", editorReducer);
