/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ActionsUnion, createAction } from "@library/state/utility";
import Quill, { RangeStatic } from "quill/core";

export const CREATE_INSTANCE = "[editorInstance] create";
export const DELETE_INSTANCE = "[editorInstance] delete";
export const SET_SELECTION = "[editorInstance] set selection";
export const CLEAR_MENTION_SELECTION = "[editorInstance] clear mention selection";

export const actions = {
    createInstance: (editorID: string | number) => createAction(CREATE_INSTANCE, { editorID }),
    deleteInstance: (editorID: string | number) => createAction(DELETE_INSTANCE, { editorID }),
    setSelection: (editorID: string | number, selection: RangeStatic | null, quill: Quill) =>
        createAction(SET_SELECTION, { editorID, selection, quill }),
};

export type ActionTypes = ActionsUnion<typeof actions>;
