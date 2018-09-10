/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { ActionsUnion, createAction } from "@library/state/utility";
import Quill, { RangeStatic } from "quill/core";

export const CREATE_INSTANCE = "[instance] create";
export const SET_SELECTION = "[instance] set selection";
export const CLEAR_MENTION_SELECTION = "[instance] clear mention selection";

export const actions = {
    createInstance: (editorID: string | number) => createAction(CREATE_INSTANCE, { editorID }),
    setSelection: (editorID: string | number, selection: RangeStatic | null, quill: Quill) =>
        createAction(SET_SELECTION, { editorID, selection, quill }),
};

export type ActionTypes = ActionsUnion<typeof actions>;
