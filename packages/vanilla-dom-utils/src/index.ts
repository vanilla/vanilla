/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// Some polyfills
import "focus-visible";
import smoothscroll from "smoothscroll-polyfill";

smoothscroll.polyfill();

export * from "./domData";
export * from "./emoji";
export * from "./EscapeListener";
export * from "./events";
export * from "./FocusWatcher";
export * from "./sanitization";
export * from "./scripts";
export * from "./shadowDom";
export * from "./TabHandler";
export * from "./visibility";
