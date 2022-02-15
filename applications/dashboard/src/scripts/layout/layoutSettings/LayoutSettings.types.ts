/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Loadable, LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { RecordID } from "@vanilla/utils";

export interface ILayoutsStoreState extends ICoreStoreState {
    layoutSettings: ILayoutsState;
}

export interface ILayoutsState {
    layoutsByID: { [key in ILayout["layoutID"]]?: Loadable<ILayout> };
    layoutsByViewType: {
        [key: string]: ILayout[];
    };
    layoutsListStatus: {
        status: LoadStatus;
        error?: any;
    };
}

export const INITIAL_LAYOUTS_STATE: ILayoutsState = {
    layoutsByID: {},
    layoutsByViewType: {},
    layoutsListStatus: {
        status: LoadStatus.PENDING,
    },
};

export interface ILayout {
    layoutID: RecordID;
    name: string;
    layoutViewType: string;
    isDefault: boolean;
    insertUserID: number;
    dateInserted: string;
    updateUserID: number;
    dateUpdated?: string;
    layoutViews: ILayoutView[];
}
export interface ILayoutView {
    layoutViewID: RecordID;
    layoutID: RecordID;
    recordID: number;
    layoutViewType: string;
    recordType: string;
    insertUserID: number;
    dateInserted: string;
    updateUserID: number;
    dateUpdated?: string;
    record: {
        name: string;
        url: string;
    };
}
export interface ILayoutViewQuery {
    layoutID: RecordID;
    recordID: number;
    recordType: string;
}
