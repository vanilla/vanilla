interface Element {
    setAttribute(name: string, value: boolean): void;
    setAttribute(name: string, value: number): void;
}

interface Window {
    Waypoint: any;
}

declare var gdn: any;

declare interface AnyObject {
    [key: string]: any;
}
