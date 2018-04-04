/* tslint:disable */

interface Element {
    setAttribute(name: string, value: boolean): void;
    setAttribute(name: string, value: number): void;
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
