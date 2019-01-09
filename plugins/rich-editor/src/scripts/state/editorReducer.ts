/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import instanceReducer from "@rich-editor/state/instance/instanceReducer";
import { combineReducers } from "redux";

const editorReducer = combineReducers({
    instances: instanceReducer,
});

export default editorReducer;
