/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, Loadable, LoadStatus } from "@library/@types/api/core";
import { IUserFragment } from "@library/@types/api/users";
import { IHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.types";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";

// TODO: Fix these interface names
export interface ILayoutsStoreState extends ICoreStoreState {
    layoutSettings: ILayoutsState;
}

export interface ILayoutsState {
    layoutsByID: Record<RecordID, Loadable<ILayoutDetails>>;
    layoutsListStatus: {
        status: LoadStatus;
        error?: any;
    };
    layoutDraft: ILayoutDraft | null;

    layoutJsonsByLayoutID: Record<RecordID, Loadable<ILayoutEdit>>;
    catalogByViewType: Partial<Record<LayoutViewType, ILayoutCatalog>>;
    catalogStatusByViewType: Partial<Record<string, Loadable<{}>>>;
    legacyStatusesByViewType: Partial<Record<LayoutViewType, ILoadable<{}>>>;
}

export const INITIAL_LAYOUTS_STATE: ILayoutsState = {
    layoutsByID: {},
    layoutsListStatus: {
        status: LoadStatus.PENDING,
    },
    layoutDraft: null,
    layoutJsonsByLayoutID: {},
    catalogByViewType: {},
    catalogStatusByViewType: {},
    legacyStatusesByViewType: {},
};

export const LAYOUT_VIEW_TYPES = [
    "home",
    "subcommunityHome",
    "discussionList",
    "categoryList",
    "nestedCategoryList",
    "discussionCategoryPage",
    "discussionThread",
    "discussionPage",
    "ideaThread",
    "questionThread",
    "knowledgeBase",
    "article",
    "guideArticle",
    "helpCenterArticle",
] as const;
export type LayoutViewType = (typeof LAYOUT_VIEW_TYPES)[number];

export interface ILayoutDetails {
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

export interface ILayoutEdit extends IEditableLayoutSpec {
    name: string;
    layoutID: ILayoutDetails["layoutID"];
}
export interface ILayoutDraft extends IEditableLayoutSpec {
    name: string;
    layoutID?: ILayoutDetails["layoutID"];
}

export enum LayoutRecordType {
    /**
     * Used exclusively to apply a layout to the top level home page.
     * Should get phased out in a future iteration now that the layout types are split.
     * @deprecated
     */
    ROOT = "root",
    // Used to apply a layout as a "default" layout for its layoutViewType
    GLOBAL = "global",
    // Used to apply a layout to specific subcommunity/siteSection
    SUBCOMMUNITY = "subcommunity",
    // Used to apply a layout to a specific category.
    CATEGORY = "category",
}

export interface ILayoutView {
    layoutViewID: RecordID;
    layoutViewType: LayoutViewType;
    layoutID: RecordID;
    recordID: number;
    recordType: LayoutRecordType;
    insertUserID: number;
    dateInserted: string;
    updateUserID: number;
    dateUpdated?: string;
    record: {
        name: string;
        url: string;
    };
}
export type LayoutViewFragment = Pick<ILayoutView, "recordID" | "recordType">;

/**
 * Interface representing a raw layout widget (like what comes back from the /api/v2/layouts/:id/edit)
 */
export interface IEditableLayoutWidget {
    $middleware?: Record<string, any>;
    $hydrate: string;
    // Any props for the widget.
    [key: string]: any;
}

export interface IEditableLayoutSpec {
    layoutViewType: LayoutViewType;
    layout: IEditableLayoutWidget[];
}

export interface ILayoutEditorPath {
    sectionIndex: number; // 0
    sectionRegion?: string; // rightTop
    sectionRegionIndex?: number; // 4
}

export interface ILayoutEditorSectionPath extends ILayoutEditorPath {}

export interface ILayoutEditorDestinationPath extends ILayoutEditorPath {
    sectionRegion: string; // rightTop
}

export interface ILayoutEditorWidgetPath extends ILayoutEditorDestinationPath {
    sectionRegionIndex: number; // 4
}

export interface IHydratedEditableWidgetProps {
    $hydrate: string;
    $componentName: string;
    $editorPath: ILayoutEditorPath;
}

export interface IHydratedEditableLayoutWidget extends IHydratedLayoutWidget<IHydratedEditableWidgetProps> {}

export interface IHydratedEditableLayoutSpec {
    layout: IHydratedEditableLayoutWidget[];
}

type ISchemaCatalog = Record<
    string,
    {
        schema: JsonSchema;
    }
>;

export type IWidgetCatalog = Record<
    string,
    {
        schema: JsonSchema;
        $reactComponent: string;
        allowedWidgetIDs?: string[];
        iconUrl?: string;
        name: string;
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
    middlewares: ISchemaCatalog;
}
