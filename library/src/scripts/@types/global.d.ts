/* eslint-disable */

import type { VanillaSanitizedHtml } from "@library/content/UserContent.types";

declare global {
    interface JQuery {
        atwho?: any; // The at-who library.
    }
    interface Window {
        gdn: any;
    }
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

/// <reference types="vite/client" />
