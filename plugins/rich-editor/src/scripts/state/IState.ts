/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import IBaseState from "@dashboard/state/IState";
import MentionTrie from "@rich-editor/state/mention/MentionTrie";
import { RangeStatic } from "quill/core";

export interface IMentionState {
    lastSuccessfulUsername: string | null;
    currentUsername: string | null;
    usersTrie: MentionTrie;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
}

export interface IEditorInstance {
    currentSelection: RangeStatic | null;
    lastGoodSelection: RangeStatic | null;
}

export interface IEditorInstanceState {
    [key: number]: IEditorInstance;
    [key: string]: IEditorInstance;
}

export default interface IState extends IBaseState {
    editor: {
        instances: IEditorInstanceState;
        mentions: IMentionState;
    };
}
