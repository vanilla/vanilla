/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Loadable, LoadStatus } from "@library/@types/api/core";
import { InPanelLayout } from "@library/carousel/Carousel.story";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { RecordID } from "@vanilla/utils";

export interface ILayoutsStoreState extends ICoreStoreState {
    layoutSettings: ILayoutsState;
}

export interface ILayoutsState {
    layoutsByID: { [key in ILayout["layoutID"]]?: Loadable<ILayout> };
    layoutsListStatus: {
        status: LoadStatus;
        error?: any;
    };
    layoutJsonDraftsByID: { [key in ILayout["layoutID"]]?: Omit<LayoutEditSchema, "layoutID"> };
    layoutJsonsByLayoutID: { [key in ILayout["layoutID"]]?: Loadable<LayoutEditSchema> };
}

export const INITIAL_LAYOUTS_STATE: ILayoutsState = {
    layoutsByID: {},
    layoutsListStatus: {
        status: LoadStatus.PENDING,
    },
    layoutJsonDraftsByID: {},
    layoutJsonsByLayoutID: {},
};

export type LayoutViewType = "home" | "discussions" | "categories";
export const LAYOUT_VIEW_TYPES = ["home", "discussions", "categories"] as LayoutViewType[];
export interface ILayout {
    layoutID: RecordID;
    name: string;
    isDefault?: boolean;
    insertUserID?: number;
    dateInserted?: string;
    updateUserID?: number;
    dateUpdated?: string;
    layoutViewType: LayoutViewType;
    layoutViews: ILayoutView[];
}

interface ILayoutDefinition {
    $hydrate: string;
    children: ILayoutDefinition[];
    [key: string]: any; //react component props
}

export type LayoutEditSchema = {
    layoutID: ILayout["layoutID"];
    name: ILayout["name"];
    layoutViewType: ILayout["layoutViewType"]; //fixme: this property is not in the backend schema
    layout: ILayoutDefinition[];
};

export interface LayoutFromPostOrPatchResponse extends ILayout, LayoutEditSchema {}

export interface ILayoutView {
    layoutViewID: RecordID;
    layoutViewType: LayoutViewType;
    layoutID: RecordID;
    recordID: number;
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
