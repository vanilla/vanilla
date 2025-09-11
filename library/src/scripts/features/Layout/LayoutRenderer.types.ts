/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { RecordID } from "@vanilla/utils";

export enum LayoutDevice {
    MOBILE = "mobile",
    DESKTOP = "desktop",
    ALL = "all",
}
export interface ILayoutQuery<T extends object = object> {
    recordID?: number | string;
    recordType?: string;
    layoutViewType: string;
    params: T | Record<string, any>;
}

export type IHydratedLayoutFragmentImpl =
    | {
          fragmentUUID: string;
          jsUrl: string;
          cssUrl: string;
          css?: string;
      }
    | {
          fragmentUUID: "system" | "styleguide";
          jsUrl?: undefined;
          cssUrl?: undefined;
          css?: undefined;
      };

export type IHydratedLayoutFragmentImpls = Record<string, IHydratedLayoutFragmentImpl>;

/**
 * Interface representing a layout widget definition that can be rendered with the layout renderer.
 * This is what comes back from /api/v2/layouts/:id/hydrate
 */
export interface IHydratedLayoutWidget<ExtraProps = {}> {
    $middleware?: {
        visibility?: {
            device?: LayoutDevice;
        };
    };
    /** The look up name of the component (lowercase) */
    $reactComponent: string;
    /** Props to be passed to the component */
    $reactProps: Record<string, IHydratedLayoutWidget | IHydratedLayoutWidget[] | any> & ExtraProps;
    $fragmentImpls?: IHydratedLayoutFragmentImpls;
}

/**
 * Interface representing a collection of layout widgets that cna be rendered with the layout renderer.
 */
export interface IHydratedLayoutSpec {
    /** An array describing components of a layout */
    layout: IHydratedLayoutWidget[];
    titleBar: IHydratedLayoutWidget;
    contexts?: IHydratedLayoutWidget[];
    redirectTo?: string;
    seo?: {
        title?: string;
        description?: string;
        htmlContents?: string;
        ["json-ld"]?: string;
        links?: string[];
        meta?: string[];
        url?: string[];
    };
    layoutID?: RecordID;
}
