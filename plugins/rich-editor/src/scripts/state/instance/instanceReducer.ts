/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as instanceActions from "./instanceActions";
import { IEditorInstanceState, IEditorInstance } from "@rich-editor/@types/store";
import Quill, { RangeStatic } from "quill/core";
import { getMentionRange } from "@rich-editor/quill/utility";

const defaultSelection = {
    index: 0,
    length: 0,
};

export const initialState: IEditorInstanceState = {};
export const defaultInstance: IEditorInstance = {
    currentSelection: defaultSelection,
    lastGoodSelection: defaultSelection,
    mentionSelection: null,
};

/**
 * Calculate the current mention selection.
 */
function calculateMentionSelection(currentSelection: RangeStatic | null, quill: Quill): RangeStatic | null {
    if (!quill.hasFocus() && !document.activeElement.classList.contains("atMentionList-suggestion")) {
        return null;
    }

    if (!currentSelection) {
        return null;
    }

    if (currentSelection.length > 0) {
        return null;
    }

    const mentionSelection = getMentionRange(quill);
    return mentionSelection;
}

/**
 * Validate that an particular editor ID has been created before certain actions are taken on it.
 */
function validateIDExistance(state: IEditorInstanceState, action: instanceActions.ActionTypes) {
    const idExists = state[action.payload.editorID];
    if (action.type === instanceActions.CREATE_INSTANCE && idExists) {
        throw new Error(`Failed to create editor instance with id ${action.payload.editorID}. Id already exists`);
    }

    if (action.type !== instanceActions.CREATE_INSTANCE && !idExists) {
        throw new Error(
            `Could not perform an action for editor ID ${
                action.payload.editorID
            } that doesn't exist. Be sure to create an instance first.`,
        );
    }
}

export default function instanceReducer(
    state = initialState,
    action: instanceActions.ActionTypes,
): IEditorInstanceState {
    switch (action.type) {
        case instanceActions.CREATE_INSTANCE: {
            validateIDExistance(state, action);
            return {
                ...state,
                [action.payload.editorID]: defaultInstance,
            };
        }
        case instanceActions.SET_SELECTION: {
            validateIDExistance(state, action);
            const { selection, editorID, quill } = action.payload;
            const instanceState = state[editorID];
            const { lastGoodSelection } = instanceState;

            return {
                ...state,
                [editorID]: {
                    ...instanceState,
                    currentSelection: selection,
                    lastGoodSelection: selection !== null ? selection : lastGoodSelection,
                    mentionSelection: calculateMentionSelection(selection, quill),
                },
            };
        }
        default: {
            return state;
        }
    }
}
