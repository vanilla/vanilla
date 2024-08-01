/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
declare module "react-scrolllock" {
    type TouchScrollableRef = (element: HTMLElement | null) => void;
    export default class ScrollLock extends React.Component<{
        lockScroll?: boolean;
        children: React.ReactNode;
        accountForScrollbars?: boolean;
        isActive?: boolean;
    }> {}
    export class TouchScrollable extends React.Component<{
        children: React.ReactNode | ((ref: TouchScrollableRef) => React.ReactNode);
    }> {}
}
