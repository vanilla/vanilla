type NormalCallback = () => void;
type PromiseCallback = () => Promise<void>;

declare type PromiseOrNormalCallback = NormalCallback | PromiseCallback;

interface Element {
    setAttribute(name: string, value: boolean): void;
    setAttribute(name: string, value: number): void;
}

interface NodeSelector {
    querySelector<E extends HTMLElement = HTMLElement>(selectors: string): E | null;
    querySelectorAll<E extends HTMLElement = HTMLElement>(selectors: string): NodeListOf<E>;
}

interface Window {
    Waypoint: any;
}

interface EventTarget extends HTMLElement {};

declare var gdn: any;

