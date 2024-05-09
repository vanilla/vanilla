/* eslint-disable */

interface Element {
    setAttribute(name: string, value: boolean): void;
    setAttribute(name: string, value: number): void;
}

interface JQuery {
    atwho?: any; // The at-who library.
}

interface Window {
    Waypoint: any;
    gdn: any;
    Promise?: PromiseConstructor;
    NodeList?: typeof NodeList;
    Symbol?: SymbolConstructor;
    CustomEvent?: typeof CustomEvent;
    Event: typeof Event;

    [key: string]: any;
}

declare interface AnyObject {
    [key: string]: any;
}

declare namespace React {
    interface IframeHTMLAttributes<T> extends HTMLAttributes<T> {
        allow: string;
    }
}

declare namespace JSX {
    interface ExtendIFrameAttributes extends React.IframeHTMLAttributes<HTMLIFrameElement> {
        allow?: string;
    }

    interface IntrinsicElements {
        iframe: React.DetailedHTMLProps<ExtendIFrameAttributes, HTMLIFrameElement>;
        "video-js": React.DetailedHTMLProps<
            React.HTMLAttributes<HTMLElement>,
            React.ClassAttributes<HTMLElement>,
            HTMLElement
        >;
    }
}

declare module "*.svg";
declare module "*.png";
declare module "*.json";
declare module "*.html";
declare module "twemoji";
declare module "tabbable";
