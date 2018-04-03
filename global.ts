/* tslint:disable */

interface Element {
    setAttribute(name: string, value: boolean): void;
    setAttribute(name: string, value: number): void;
}

interface Window {
    Waypoint: any;
    gdn: any;
}

declare interface AnyObject {
    [key: string]: any;
}
