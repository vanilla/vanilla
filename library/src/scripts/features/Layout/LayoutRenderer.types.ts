/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export enum LayoutDevice {
    MOBILE = "mobile",
    DESKTOP = "desktop",
    ALL = "all",
}
export interface ILayoutQuery {
    recordID?: number | string;
    recordType?: string;
    layoutViewType: string;
    params: {
        [key: string]: any;
    };
}

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
}

/**
 * Interface representing a collection of layout widgets that cna be rendered with the layout renderer.
 */
export interface IHydratedLayoutSpec {
    /** An array describing components of a layout */
    layout: IHydratedLayoutWidget[];
}
