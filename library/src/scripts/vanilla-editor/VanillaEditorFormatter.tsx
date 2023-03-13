/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { unwrapBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/unwrapBlockquote";
import { ELEMENT_SPOILER } from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { unwrapSpoiler } from "@library/vanilla-editor/plugins/spoilerPlugin/unwrapSpoiler";
import { MyEditor } from "@library/vanilla-editor/typescript";
import {
    ELEMENT_BLOCKQUOTE,
    ELEMENT_CODE_BLOCK,
    ELEMENT_CODE_LINE,
    ELEMENT_H2,
    ELEMENT_H3,
    ELEMENT_H4,
    ELEMENT_H5,
    ELEMENT_LI,
    ELEMENT_OL,
    ELEMENT_PARAGRAPH,
    ELEMENT_UL,
    focusEditor,
    getAboveNode,
    getListItemEntry,
    indentListItems,
    isFirstChild,
    someNode,
    TElement,
    toggleCodeBlock,
    toggleList,
    toggleNodeType,
    unindentListItems,
    unwrapList,
    unwrapNodes,
} from "@udecode/plate-headless";
import { Path } from "slate";

export class VanillaEditorFormatter {
    public constructor(private editor: MyEditor) {}

    private isElement = (type: string): boolean => {
        return !!this.editor?.selection && someNode(this.editor, { match: { type: type } });
    };

    private toggleElement = (type: string) => {
        if (this.isOrderedList() || this.isUnorderedList()) {
            unwrapList(this.editor);
        }

        if (this.isBlockquote()) {
            unwrapBlockquote(this.editor);
        }

        if (this.isCodeBlock()) {
            unwrapNodes(this.editor, {
                match: { type: ELEMENT_CODE_LINE },
            });
        }

        if (this.isSpoiler()) {
            unwrapSpoiler(this.editor);
        }

        toggleNodeType(this.editor, {
            activeType: type,
            inactiveType: type,
        });

        focusEditor(this.editor);
    };

    private isListElement = (type: string): boolean => {
        if (!this.editor?.selection) {
            return false;
        }

        const listEntry = getListItemEntry(this.editor)?.list;
        if (!listEntry) {
            return false;
        }

        const [listElement, listPath] = listEntry;

        return listElement.type === type;
    };

    public isOrderedList = (): boolean => {
        return this.isListElement(ELEMENT_OL);
    };
    public orderedList = () => {
        toggleList(this.editor, { type: ELEMENT_OL });
        focusEditor(this.editor);
    };

    public isUnorderedList(): boolean {
        return this.isListElement(ELEMENT_UL);
    }
    public unorderedList = () => {
        toggleList(this.editor, { type: ELEMENT_UL });
        focusEditor(this.editor);
    };

    public canIndentList = (): boolean => {
        if (!this.editor?.selection) {
            return false;
        }

        const itemEntry = getListItemEntry(this.editor)?.listItem;
        if (!itemEntry) {
            return false;
        }

        const [listItem, listItemPath] = itemEntry;

        return !isFirstChild(listItemPath);
    };

    public canOutdentList = (): boolean => {
        if (!this.editor?.selection) {
            return false;
        }

        const itemEntry = getListItemEntry(this.editor)?.listItem;
        if (!itemEntry) {
            return false;
        }

        const listAbove = getAboveNode(this.editor, {
            block: true,
            match: (node: TElement) => node.type === ELEMENT_LI,
            at: itemEntry[1],
        });

        // We can outdent if we are nested in another list.

        return !!listAbove && !Path.equals(itemEntry[1], listAbove[1]);
    };

    public indentList = () => {
        indentListItems(this.editor);
    };

    public outdentList = () => {
        unindentListItems(this.editor);
    };

    public isH2 = (): boolean => {
        return this.isElement(ELEMENT_H2);
    };
    public h2 = (): void => {
        this.toggleElement(ELEMENT_H2);
    };
    public isH3 = (): boolean => {
        return this.isElement(ELEMENT_H3);
    };
    public h3 = (): void => {
        this.toggleElement(ELEMENT_H3);
    };
    public isH4 = (): boolean => {
        return this.isElement(ELEMENT_H4);
    };
    public h4 = (): void => {
        this.toggleElement(ELEMENT_H4);
    };
    public isH5 = (): boolean => {
        return this.isElement(ELEMENT_H5);
    };
    public h5 = (): void => {
        this.toggleElement(ELEMENT_H5);
    };

    public isCodeBlock = (): boolean => {
        return this.isElement(ELEMENT_CODE_BLOCK);
    };
    public codeBlock = (): void => {
        toggleCodeBlock(this.editor);
    };
    public isBlockquote = (): boolean => {
        return this.isElement(ELEMENT_BLOCKQUOTE);
    };
    public blockquote = (): void => {
        if (this.isBlockquote()) {
            unwrapBlockquote(this.editor);
        } else {
            this.toggleElement(ELEMENT_BLOCKQUOTE);
        }
    };
    public isSpoiler = (): boolean => {
        return this.isElement(ELEMENT_SPOILER);
    };
    public spoiler = (): void => {
        if (this.isSpoiler()) {
            unwrapSpoiler(this.editor);
        } else {
            this.toggleElement(ELEMENT_SPOILER);
        }
    };

    public isParagraph = (): boolean => {
        return this.isElement(ELEMENT_PARAGRAPH);
    };
    public paragraph = (): void => {
        this.toggleElement(ELEMENT_PARAGRAPH);
    };
}
