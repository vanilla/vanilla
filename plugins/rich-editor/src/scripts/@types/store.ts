/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IStoreState as IBaseStoreState } from "@dashboard/@types/state";
import { RangeStatic } from "quill/core";

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
    };
}
