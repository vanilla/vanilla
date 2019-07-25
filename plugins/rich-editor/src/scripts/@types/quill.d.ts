/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/* eslint-disable */

declare module "quill/core" {
    import HistoryModule from "@rich-editor/quill/HistoryModule";
    import SelectionModule from "quill/modules/selection";
    import Blot from "parchment/dist/src/blot/abstract/shadow";
    import Container from "parchment/dist/src/blot/abstract/container";
    import ClipboardModule from "quill/modules/clipboard";

    /**
     * A stricter type definition would be:
     *
     *   type DeltaOperation ({ insert: any } | { delete: number } | { retain: number }) & OptionalAttributes;
     *
     *  But this would break a lot of existing code as it would require manual discrimination of the union types.
     */
    export type DeltaOperation = { insert?: any; delete?: number; retain?: number } & OptionalAttributes;
    export type Sources = "api" | "user" | "silent";

    export interface Key {
        key: string;
        shortKey?: boolean;
    }

    export interface StringMap extends IFormats {}
    export interface OptionalAttributes {
        attributes?: StringMap;
    }

    export interface IFormats {
        [formatName: string]: any;
    }

    export interface KeyboardStatic {
        addBinding(key: Key, callback: (range: RangeStatic, context: any) => void): void;
        addBinding(key: Key, context: any, callback: (range: RangeStatic, context: any) => void): void;
    }

    export interface QuillOptionsStatic {
        debug?: string;
        modules?: StringMap;
        placeholder?: string;
        readOnly?: boolean;
        theme?: string;
        formats?: string[];
        bounds?: HTMLElement | string;
        scrollingContainer?: HTMLElement | string;
        strict?: boolean;
    }

    export interface BoundsStatic {
        bottom: number;
        left: number;
        right: number;
        top: number;
        height: number;
        width: number;
    }

    export interface DeltaStatic {
        ops?: DeltaOperation[];
        retain(length: number, attributes?: StringMap): DeltaStatic;
        delete(length: number): DeltaStatic;
        filter(predicate: (op: DeltaOperation) => boolean): DeltaOperation[];
        forEach(predicate: (op: DeltaOperation) => void): void;
        insert(text: any, attributes?: StringMap): DeltaStatic;
        map<T>(predicate: (op: DeltaOperation) => T): T[];
        partition(predicate: (op: DeltaOperation) => boolean): [DeltaOperation[], DeltaOperation[]];
        reduce<T>(predicate: (acc: T, curr: DeltaOperation, idx: number, arr: DeltaOperation[]) => T, initial: T): T;
        chop(): DeltaStatic;
        length(): number;
        slice(start?: number, end?: number): DeltaStatic;
        compose(other: DeltaStatic): DeltaStatic;
        concat(other: DeltaStatic): DeltaStatic;
        diff(other: DeltaStatic, index?: number): DeltaStatic;
        eachLine(
            predicate: (line: DeltaStatic, attributes: StringMap, idx: number) => any,
            newline?: string,
        ): DeltaStatic;
        transform(index: number, priority?: boolean): number;
        transform(other: DeltaStatic, priority: boolean): DeltaStatic;
        transformPosition(index: number, priority?: boolean): number;
    }

    export interface RangeStatic {
        index: number;
        length: number;
    }

    export class RangeStatic implements RangeStatic {
        constructor();
        index: number;
        length: number;
    }

    export type TextChangeHandler = (delta: DeltaStatic, oldContents: DeltaStatic, source: Sources) => any;
    export type SelectionChangeHandler = (range: RangeStatic, oldRange: RangeStatic, source: Sources) => any;
    export type EditorChangeHandler =
        | ((name: "text-change", delta: DeltaStatic, oldContents: DeltaStatic, source: Sources) => any)
        | ((name: "selection-change", range: RangeStatic, oldRange: RangeStatic, source: Sources) => any);
    export type ScrollEventHandler = (source: Sources, context: object) => any;

    type TextCallback = (eventName: "text-change", handler: TextChangeHandler) => EventEmitter;
    type SelectionCallback = (eventName: "selection-change", handler: SelectionChangeHandler) => EventEmitter;
    type ChangeCallback = (eventName: "editor-change", handler: EditorChangeHandler) => EventEmitter;
    type ScrollEventCallback = (
        eventName: "scroll-optimize" | "scroll-before-update" | "scroll-update",
        handler: ScrollEventHandler,
    ) => EventEmitter;

    export class EventEmitter {
        static events: {
            EDITOR_CHANGE: "editor-change";
            SCROLL_BEFORE_UPDATE: "scroll-before-update";
            SCROLL_OPTIMIZE: "scroll-optimize";
            SCROLL_UPDATE: "scroll-update";
            SELECTION_CHANGE: "selection-change";
            TEXT_CHANGE: "text-change";
        };

        static sources: {
            API: "api";
            SILENT: "silent";
            USER: "user";
        };

        on: (
            eventName:
                | "text-change"
                | "editor-change"
                | "selection-change"
                | "scroll-optimize"
                | "scroll-before-update"
                | "scroll-update",
            handler: TextChangeHandler | SelectionChangeHandler | EditorChangeHandler | ScrollEventHandler,
        ) => EventEmitter;
        once: (
            eventName:
                | "text-change"
                | "editor-change"
                | "selection-change"
                | "scroll-optimize"
                | "scroll-before-update"
                | "scroll-update",
            handler: TextChangeHandler | SelectionChangeHandler | EditorChangeHandler | ScrollEventHandler,
        ) => EventEmitter;
        off: (
            eventName:
                | "text-change"
                | "editor-change"
                | "selection-change"
                | "scroll-optimize"
                | "scroll-before-update"
                | "scroll-update",
            handler: TextChangeHandler | SelectionChangeHandler | EditorChangeHandler | ScrollEventHandler,
        ) => EventEmitter;
    }

    class Quill extends EventEmitter {
        root: HTMLDivElement;
        clipboard: ClipboardModule;
        selection: SelectionModule;
        scroll: Container;
        container: HTMLDivElement;
        options: AnyObject;
        history: HistoryModule;

        // Custom
        getLastGoodSelection(): RangeStatic;

        constructor(container: string | Element, options?: QuillOptionsStatic);
        deleteText(index: number, length: number, source?: Sources): DeltaStatic;
        disable(): void;
        enable(enabled?: boolean): void;
        getContents(index?: number, length?: number): DeltaStatic;
        getLength(): number;
        getText(index?: number, length?: number): string;
        insertEmbed(index: number, type: string, value: any, source?: Sources): DeltaStatic;
        insertText(index: number, text: string, source?: Sources): DeltaStatic;
        insertText(index: number, text: string, source?: Sources): DeltaStatic;
        insertText(index: number, text: string, format: string, value: any, source?: Sources): DeltaStatic;
        insertText(index: number, text: string, formats: StringMap, source?: Sources): DeltaStatic;
        /**
         * @deprecated Remove in 2.0. Use clipboard.dangerouslyPasteHTML(index: number, html: string, source: Sources)
         */
        pasteHTML(index: number, html: string, source?: Sources): string;
        /**
         * @deprecated Remove in 2.0. Use clipboard.dangerouslyPasteHTML(html: string, source: Sources): void;
         */
        pasteHTML(html: string, source?: Sources): string;
        setContents(delta: DeltaStatic | DeltaOperation[], source?: Sources): DeltaStatic;
        setText(text: string, source?: Sources): DeltaStatic;
        update(source?: Sources): void;
        updateContents(delta: DeltaStatic | DeltaOperation[], source?: Sources): DeltaStatic;

        format(name: string, value: any, source?: Sources): DeltaStatic;
        formatLine(index: number, length: number, source?: Sources): DeltaStatic;
        formatLine(index: number, length: number, format: string, value: any, source?: Sources): DeltaStatic;
        formatLine(index: number, length: number, formats: StringMap, source?: Sources): DeltaStatic;
        formatText(index: number, length: number, source?: Sources): DeltaStatic;
        formatText(index: number, length: number, format: string, value: any, source?: Sources): DeltaStatic;
        formatText(index: number, length: number, formats: StringMap, source?: Sources): DeltaStatic;
        getFormat(range?: RangeStatic): StringMap;
        getFormat(index: number, length?: number): StringMap;
        removeFormat(index: number, length: number, source?: Sources): DeltaStatic;

        blur(): void;
        focus(): void;
        getBounds(index: number, length?: number): BoundsStatic;
        getSelection(focus?: boolean): RangeStatic;
        hasFocus(): boolean;
        setSelection(index: number, length: number, source?: Sources): void;
        setSelection(range: RangeStatic, source?: Sources): void;

        // static methods: debug, import, register, find
        static debug(level: string | boolean): void;
        static import(path: string): any;
        static register(path: string, def: any, suppressWarning?: boolean): void;
        static register(defs: StringMap, suppressWarning?: boolean): void;
        static find(domNode: Node, bubble?: boolean): Quill | any;

        addContainer(classNameOrDomNode: string | Node, refNode?: Node): any;
        getModule(name: string): any;

        // Blot interface is not exported on Parchment
        getIndex(blot: any): number;
        getLeaf(index: number): any;
        getLine(index: number): [any, number];
        getLines(index?: number, length?: number): any[];
        getLines(range: RangeStatic): any[];
    }

    export { Container, Blot };

    export default Quill;
}

declare module "quill/blots/block" {
    import Block from "parchment/dist/src/blot/block";
    import Embed from "parchment/dist/src/blot/embed";
    import { Blot, DeltaOperation } from "quill/core";

    export class BlockEmbed extends Embed {}
    export default class BlockBlot extends Block {
        protected cache: any = {};
        public delta(): [];
    }
}

declare module "quill/blots/inline" {
    import Inline from "parchment/dist/src/blot/inline";
    export default Inline;
}

declare module "quill/blots/container" {
    import Container from "parchment/dist/src/blot/abstract/container";
    export default Container;
}

declare module "quill/blots/scroll" {
    import Scroll from "parchment/dist/src/blot/scroll";
    export default Scroll;
}

declare module "quill/blots/embed" {
    import Embed from "parchment/dist/src/blot/embed";
    export default Embed;
}

declare module "quill/blots/text" {
    import Text from "parchment/dist/src/blot/text";
    export default Text;
}

declare module "quill/core/emitter";
declare module "quill/core/module" {
    import Quill from "quill/core";
    export default class Module {
        constructor(protected quill: Quill, protected options: AnyObject);
        public init();
    }
}

declare module "quill/formats/code" {
    import Inline from "quill/blots/inline";
    import Block from "quill/blots/block";

    export class Code extends Inline {}
    export default class CodeBlock extends Block {}
}
declare module "quill/core/theme" {
    import Module from "quill/core/module";
    export default class Theme extends Module {
        addModule(moduleKey: string);
    }
}
declare module "quill/modules/clipboard" {
    import { DeltaStatic, Sources } from "quill/core";
    import Module from "quill/core/module";
    export default class ClipboardModule extends Module {
        public container: HTMLElement;

        addMatcher(
            selectorOrNodeType: string | number,
            callback: (node: any, delta: DeltaStatic) => DeltaStatic | undefined,
        ): void;
        dangerouslyPasteHTML(html: string, source?: Sources): void;
        dangerouslyPasteHTML(index: number, html: string, source?: Sources): void;
        convert(html?: string): DeltaStatic;
    }
}
declare module "quill/modules/formula";
declare module "quill/modules/history" {
    import Module from "quill/core/module";
    import { DeltaStatic } from "quill/core";

    interface UndoItem {
        undo: DeltaStatic;
        redo: DeltaStatic;
    }

    export default class History extends Module {
        protected stack: {
            undo: UndoItem[];
            redo: UndoItem[];
        };
        protected lastRecorded: number;
        protected ignoreChange: boolean;
        public clear(): void;
        public undo(): void;
        public redo(): void;
        public cutoff(): void;
        public change(source: "undo" | "redo", dest: "undo" | "redo"): void;
        public record(changeDelta: DeltaStatic, oldDelta: DeltaStatic): void;
    }
}
declare module "quill/modules/keyboard" {
    import Module from "quill/core/module";
    import { RangeStatic } from "quill";

    interface Keys {
        BACKSPACE: 8;
        TAB: 9;
        ENTER: 13;
        ESCAPE: 27;
        LEFT: 37;
        UP: 38;
        RIGHT: 39;
        DOWN: 40;
        DELETE: 46;
    }

    export interface KeyBinding {
        key: string | number;
        shiftKey?: boolean;
        metaKey?: boolean;
        ctrlKey?: boolean;
        altKey?: boolean;
        shortKey?: boolean;
    }

    type Formats =
        | string[]
        | {
              [key: string]: string | boolean | number;
          };

    interface ConfigurationContext {
        collapsed?: boolean;
        format?: Formats;
        offset?: number;
        empty?: boolean;
        prefix?: RegExp;
        suffix?: RegExp;
    }

    interface HandlerContext extends ConfigurationContext {
        collapsed: boolean;
        format: Formats;
        offset: number;
        empty: boolean;
        prefix: string;
        suffix: string;
        event: KeyboardEvent;
    }

    interface IBindingObject extends ConfigurationContext, KeyBinding {
        handler: KeyboardHandler;
    }
    export type BindingObject = IBindingObject | false | undefined;

    type KeyboardHandler = (selectedRange: RangeStatic, context: HandlerContext) => boolean | null | undefined | void; // False to prevent default.

    export default class KeyboardModule extends Module {
        public static match(event: KeyboardEvent, binding: KeyBinding | string | number);
        public static keys: Keys;
        addBinding(key: KeyBinding | string | number, context: Context, handler: KeyboardHandler): void;
    }
}
declare module "quill/modules/syntax" {
    import { DeltaStatic, Sources } from "quill/core";
    import Module from "quill/core/module";
    import BaseCodeBlock from "quill/formats/code";

    export class CodeBlock extends BaseCodeBlock {}

    export const CodeToken: any;

    export default class SyntaxModule extends Module {
        public container: HTMLElement;
        public static register();
        public highlight();
    }
}
declare module "quill/modules/toolbar";
declare module "quill/formats/*";
declare module "quill-delta";
