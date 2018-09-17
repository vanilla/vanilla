/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IStoreState as IBaseStoreState } from "@dashboard/@types/state";
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
    lastGoodSelection: RangeStatic;
    mentionSelection: RangeStatic | null;
}

export interface IEditorInstanceState {
    [key: number]: IEditorInstance;
    [key: string]: IEditorInstance;
}

export interface IStoreState extends IBaseStoreState {
    editor: {
        instances: IEditorInstanceState;
        mentions: IMentionState;
    };
}
