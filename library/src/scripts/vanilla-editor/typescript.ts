/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IMentionSuggestionData } from "@library/editor/pieces/MentionSuggestion";
import { IBaseEmbedData } from "@library/embeddedContent/embedService";
import { IRichEmbedElement } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { AutoformatRule } from "@udecode/plate-autoformat";
import { ELEMENT_BLOCKQUOTE } from "@udecode/plate-block-quote";
import { ELEMENT_CODE_BLOCK, ELEMENT_CODE_LINE } from "@udecode/plate-code-block";
import {
    CreatePlateEditorOptions,
    DOMHandler,
    Decorate,
    DecorateEntry,
    EDescendant,
    EElement,
    EElementEntry,
    EElementOrText,
    EMarks,
    ENode,
    ENodeEntry,
    EText,
    ETextEntry,
    InjectComponent,
    InjectProps,
    KeyboardHandler,
    NoInfer,
    OnChange,
    OverrideByKey,
    PlateEditor,
    PlatePlugin,
    PlatePluginComponent,
    PlatePluginInsertData,
    PlatePluginProps,
    PlateProps,
    PluginOptions,
    SerializeHtml,
    TElement,
    TNodeEntry,
    TReactEditor,
    TText,
    WithOverride,
    createPlateEditor,
    createPluginFactory,
    createPlugins,
    createTEditor,
    getTEditor,
    useEditorRef,
    useEditorState,
    usePlateEditorRef,
    usePlateEditorState,
    usePlateSelectors,
    PlateId,
} from "@udecode/plate-common";
import { ELEMENT_H1, ELEMENT_H2, ELEMENT_H3 } from "@udecode/plate-heading";
import { ELEMENT_HR } from "@udecode/plate-horizontal-rule";
import { ELEMENT_LINK, TLinkElement } from "@udecode/plate-link";
import { ELEMENT_LI, ELEMENT_OL, ELEMENT_TODO_LI, ELEMENT_UL, TTodoListItemElement } from "@udecode/plate-list";
import { ELEMENT_MENTION, ELEMENT_MENTION_INPUT, TMentionElement, TMentionInputElement } from "@udecode/plate-mention";
import { ELEMENT_PARAGRAPH } from "@udecode/plate-paragraph";
import { ELEMENT_TABLE, ELEMENT_TD, ELEMENT_TR, TTableElement } from "@udecode/plate-table";

/**
 * Text
 */

export type EmptyText = {
    text: "";
};

export type PlainText = {
    text: string;
};

export interface RichText extends TText {
    bold?: boolean;
    italic?: boolean;
    underline?: boolean;
    strikethrough?: boolean;
    code?: boolean;
    kbd?: boolean;
    subscript?: boolean;
}

/**
 * Inline Elements
 */

export interface IVanillaLinkElement extends TLinkElement {
    type: typeof ELEMENT_LINK;
    children: RichText[];
    embedData?: IBaseEmbedData;
    forceBasicLink?: boolean;
}

export interface MyMentionInputElement extends TMentionInputElement {
    type: typeof ELEMENT_MENTION_INPUT;
    children: [PlainText];
}

export interface MyMentionElement extends TMentionElement, IMentionSuggestionData {
    type: typeof ELEMENT_MENTION;
    children: [EmptyText];
}

export type MyInlineElement = IVanillaLinkElement | MyMentionElement | MyMentionInputElement | IRichEmbedElement;
export type MyInlineDescendant = MyInlineElement | RichText;
export type MyInlineChildren = MyInlineDescendant[];

/**
 * Block props
 */

export interface MyIndentProps {
    indent?: number;
}

export interface MyIndentListProps extends MyIndentProps {
    listStart?: number;
    listStyleType?: string;
}

export interface MyBlockElement extends TElement, MyIndentListProps {
    id?: string;
}

/**
 * Blocks
 */

export interface MyParagraphElement extends MyBlockElement {
    type: typeof ELEMENT_PARAGRAPH;
    children: MyInlineChildren;
}

export interface MyH1Element extends MyBlockElement {
    type: typeof ELEMENT_H1;
    children: MyInlineChildren;
}

export interface MyH2Element extends MyBlockElement {
    type: typeof ELEMENT_H2;
    children: MyInlineChildren;
}

export interface MyH3Element extends MyBlockElement {
    type: typeof ELEMENT_H3;
    children: MyInlineChildren;
}

export interface MyBlockquoteElement extends MyBlockElement {
    type: typeof ELEMENT_BLOCKQUOTE;
    children: MyInlineChildren;
}

export interface MyCodeBlockElement extends MyBlockElement {
    type: typeof ELEMENT_CODE_BLOCK;
    children: MyCodeLineElement[];
}

export interface MyCodeLineElement extends TElement {
    type: typeof ELEMENT_CODE_LINE;
    children: PlainText[];
}

export interface MyTableElement extends TTableElement, MyBlockElement {
    type: typeof ELEMENT_TABLE;
    children: MyTableRowElement[];
}

export interface MyTableRowElement extends TElement {
    type: typeof ELEMENT_TR;
    children: MyTableCellElement[];
}

export interface MyTableCellElement extends TElement {
    type: typeof ELEMENT_TD;
    children: MyNestableBlock[];
}

export interface MyBulletedListElement extends TElement, MyBlockElement {
    type: typeof ELEMENT_UL;
    children: MyListItemElement[];
}

export interface MyNumberedListElement extends TElement, MyBlockElement {
    type: typeof ELEMENT_OL;
    children: MyListItemElement[];
}

export interface MyListItemElement extends TElement, MyBlockElement {
    type: typeof ELEMENT_LI;
    children: MyInlineChildren;
}

export interface MyTodoListElement extends TTodoListItemElement, MyBlockElement {
    type: typeof ELEMENT_TODO_LI;
    children: MyInlineChildren;
}

export interface MyHrElement extends MyBlockElement {
    type: typeof ELEMENT_HR;
    children: [EmptyText];
}

export type MyNestableBlock = MyParagraphElement;

export type MyBlock = Exclude<MyElement, MyInlineElement>;
export type MyBlockEntry = TNodeEntry<MyBlock>;

export type MyRootBlock =
    | MyParagraphElement
    | MyH1Element
    | MyH2Element
    | MyH3Element
    | MyBlockquoteElement
    | MyCodeBlockElement
    | MyTableElement
    | MyBulletedListElement
    | MyNumberedListElement
    | MyTodoListElement
    | IRichEmbedElement
    | MyHrElement;

export type MyValue = MyRootBlock[];

/**
 * Editor types
 */

export type MyEditor = PlateEditor<MyValue> & { isDragging?: boolean };
export type MyReactEditor = TReactEditor<MyValue>;
export type MyNode = ENode<MyValue>;
export type MyNodeEntry = ENodeEntry<MyValue>;
export type MyElement = EElement<MyValue>;
export type MyElementEntry = EElementEntry<MyValue>;
export type MyText = EText<MyValue>;
export type MyTextEntry = ETextEntry<MyValue>;
export type MyElementOrText = EElementOrText<MyValue>;
export type MyDescendant = EDescendant<MyValue>;
export type MyMarks = EMarks<MyValue>;
export type MyMark = keyof MyMarks;

/**
 * Plate types
 */

export type MyDecorate<P = PluginOptions> = Decorate<P, MyValue, MyEditor>;
export type MyDecorateEntry = DecorateEntry<MyValue>;
export type MyDOMHandler<P = PluginOptions> = DOMHandler<P, MyValue, MyEditor>;
export type MyInjectComponent = InjectComponent<MyValue>;
export type MyInjectProps = InjectProps<MyValue>;
export type MyKeyboardHandler<P = PluginOptions> = KeyboardHandler<P, MyValue, MyEditor>;
export type MyOnChange<P = PluginOptions> = OnChange<P, MyValue, MyEditor>;
export type MyOverrideByKey = OverrideByKey<MyValue, MyEditor>;
export type MyPlatePlugin<P = PluginOptions> = PlatePlugin<P, MyValue, MyEditor>;
export type MyPlatePluginInsertData = PlatePluginInsertData<MyValue>;
export type MyPlatePluginProps = PlatePluginProps<MyValue>;
export type MyPlateProps = PlateProps<MyValue, MyEditor>;
export type MySerializeHtml = SerializeHtml<MyValue>;
export type MyWithOverride<P = PluginOptions> = WithOverride<P, MyValue, MyEditor>;

/**
 * Plate store, Slate context
 */

export const getMyEditor = (editor: MyEditor) => getTEditor<MyValue, MyEditor>(editor);
export const useMyEditorRef = () => useEditorRef<MyValue, MyEditor>();
export const useMyEditorState = () => useEditorState<MyValue, MyEditor>();
export const useMyPlateEditorRef = (id?: PlateId) => usePlateEditorRef<MyValue, MyEditor>(id);
export const useMyPlateEditorState = (id?: PlateId) => usePlateEditorState<MyValue, MyEditor>(id);
export const useMyPlateSelectors = (id?: PlateId) => usePlateSelectors<MyValue, MyEditor>(id);

/**
 * Utils
 */
export const createMyEditor = () => createTEditor() as MyEditor;
export const createMyPlateEditor = (options: CreatePlateEditorOptions<MyValue, MyEditor> = {}) =>
    createPlateEditor<MyValue, MyEditor>(options);
export const createMyPluginFactory = <P = PluginOptions>(defaultPlugin: PlatePlugin<NoInfer<P>, MyValue, MyEditor>) =>
    createPluginFactory(defaultPlugin);
export const createMyPlugins = (
    plugins: MyPlatePlugin[],
    options?: {
        components?: Record<string, PlatePluginComponent>;
        overrideByKey?: MyOverrideByKey;
    },
) => createPlugins<MyValue, MyEditor>(plugins, options);

export type MyAutoformatRule = AutoformatRule<MyValue, MyEditor>;
