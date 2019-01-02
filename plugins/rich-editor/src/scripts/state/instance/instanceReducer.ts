/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as instanceActions from "@rich-editor/state/instance/instanceActions";
import { IEditorInstanceState, IEditorInstance } from "@rich-editor/@types/store";
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
        case instanceActions.DELETE_INSTANCE: {
            validateIDExistance(state, action);
            const newState = { ...state };
            delete newState[action.payload.editorID];
            return newState;
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
                    mentionSelection: getMentionRange(quill, selection),
                },
            };
        }
        default: {
            return state;
        }
    }
}
