/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { IEditorInstanceState, IEditorInstance } from "@rich-editor/state/IState";
import * as instanceActions from "./instanceActions";
import uniqueId from "lodash/uniqueId";

const initialState: IEditorInstanceState = {};
const defaultInstance: IEditorInstance = {
    currentSelection: null,
    lastGoodSelection: null,
};

/**
 * Validate that an particular editor ID has been created before certain actions are taken on it.
 */
function validateID(state: IEditorInstanceState, action: instanceActions.ActionTypes) {
    if (action.type !== instanceActions.CREATE_INSTANCE && !state[action.payload.editorID]) {
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
            return {
                ...state,
                [action.payload.editorID]: defaultInstance,
            };
        }
        case instanceActions.SET_SELECTION: {
            validateID(state, action);
            const { selection, editorID } = action.payload;
            const instanceState = state[editorID];
            const { lastGoodSelection } = instanceState;
            return {
                ...state,
                [editorID]: {
                    ...instanceState,
                    currentSelection: selection,
                    lastGoodSelection: selection !== null ? selection : lastGoodSelection,
                },
            };
        }
        default: {
            return state;
        }
    }
}
