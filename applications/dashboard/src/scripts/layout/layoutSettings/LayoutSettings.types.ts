/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Loadable, LoadStatus } from "@library/@types/api/core";
import { IUserFragment } from "@library/@types/api/users";
import { InPanelLayout } from "@library/carousel/Carousel.story";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { RecordID } from "@vanilla/utils";
import { ILayout as ILayoutSchema } from "@library/features/Layout/Layout";
import { JsonSchema } from "@vanilla/json-schema-forms";

// TODO: Fix these interface names
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
    catalogByViewType: {
        [key in ILayoutCatalog["layoutViewType"]]?: ILayoutCatalog;
    };
    catalogStatusByViewType: {
        [key in ILayoutCatalog["layoutViewType"]]?: {
            status: LoadStatus;
            error?: any;
        };
    };
}

export const INITIAL_LAYOUTS_STATE: ILayoutsState = {
    layoutsByID: {},
    layoutsListStatus: {
        status: LoadStatus.PENDING,
    },
    layoutJsonDraftsByID: {},
    layoutJsonsByLayoutID: {},
    catalogByViewType: {},
    catalogStatusByViewType: {},
};

export type LayoutViewType = "home" | "discussions" | "categories";
export const LAYOUT_VIEW_TYPES = ["home", "discussions", "categories"] as LayoutViewType[];
export interface ILayout {
    layoutID: RecordID;
    name: string;
    isDefault?: boolean;
    insertUserID: number;
    dateInserted?: string;
    updateUserID?: number;
    dateUpdated?: string;
    insertUser?: IUserFragment;
    updateUser?: IUserFragment;
    layoutViewType: LayoutViewType;
    layoutViews: ILayoutView[];
}

interface ILayoutDefinition {
    $hydrate: string;
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
export interface IEditableLayout extends ILayoutSchema {
    layoutID: ILayout["layoutID"];
    name: ILayout["name"];
}

type ISchemaCatalog = Record<
    string,
    {
        schema: JsonSchema;
    }
>;

type IWidgetCatalog = Record<
    string,
    {
        schema: JsonSchema;
        $reactComponent: string;
        recommendedWidgets: Array<{ widgetID: string; widgetName: string }>;
    }
>;

export type LayoutSectionID =
    | "react.section.1-column"
    | "react.section.2-column"
    | "react.section.3-column"
    | "react.section.full-width";

export interface ILayoutCatalog {
    layoutViewType: LayoutViewType;
    /** A mapping of all available layout hydration params to their schemas. */
    layoutParams: ISchemaCatalog;
    /** A mapping of widgetType to widget schema for all available widgets. */
    widgets: IWidgetCatalog;
    /** A mapping of widgetType to widget schema for all available assets. */
    assets: IWidgetCatalog;
    /** A mapping of widgetType to widget schema for all available sections. */
    sections: IWidgetCatalog;
    /** A mapping of middlewareType to middleware schema for all available middleware. */
    middleware: ISchemaCatalog;
}
