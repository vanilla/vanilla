/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/* tslint:disable:max-classes-per-file */
declare module "react-scrolllock" {
    export default class ScrollLock extends React.Component<{
        lockScroll?: boolean;
        children: JSX.Element | null;
        accountForScrollbars?: boolean;
        isActive?: boolean;
    }> {}
    export class TouchScrollable extends React.Component<{ children: JSX.Element | null }> {}
}
