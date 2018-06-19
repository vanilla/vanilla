/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import mentionReducer from "./mentionReducer";
import { combineReducers } from "redux";

const editorReducer = combineReducers({
    mentions: mentionReducer,
});

export default editorReducer;
