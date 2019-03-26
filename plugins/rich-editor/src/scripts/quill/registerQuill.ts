/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// Quill Built-ins
import Quill from "quill/core";
import Bold from "quill/formats/bold";
import Italic from "quill/formats/italic";
import Link from "quill/formats/link";
import Strike from "quill/formats/strike";
import { IndentClass as Indent } from "quill/formats/indent";

// Custom Inline Blots
import CodeInlineBlot from "@rich-editor/quill/blots/inline/CodeBlot";

// Custom Embed Blots
import EmojiBlot from "@rich-editor/quill/blots/embeds/EmojiBlot";
import ExternalEmbedBlot from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import EmbedErrorBlot from "@rich-editor/quill/blots/embeds/ErrorBlot";
import MentionBlot from "@rich-editor/quill/blots/embeds/MentionBlot";
import MentionComboBoxBlot from "@rich-editor/quill/blots/embeds/MentionComboBoxBlot";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";

// Custom Block Blot
import SpoilerLineBlot, { SpoilerWrapperBlot, SpoilerContentBlot } from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import BlockquoteLineBlot, {
    BlockquoteWrapperBlot,
    BlockquoteContentBlot,
} from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";

// Custom Modules/Themes
import VanillaTheme from "@rich-editor/quill/VanillaTheme";
import FocusModule from "@rich-editor/quill/FocusModule";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import HistoryModule from "@rich-editor/quill/HistoryModule";
import ClipboardModule from "@rich-editor/quill/ClipboardModule";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import SyntaxModule from "@rich-editor/quill/SyntaxModule";
import {
    OrderedListGroup,
    UnorderedListGroup,
    ListItem,
    ListItemWrapper,
} from "@rich-editor/quill/blots/blocks/ListBlot";

let wasRegistered = false;

export default function registerQuill() {
    if (wasRegistered) {
        return;
    }
    wasRegistered = true;
    Quill.register(
        {
            // Block formats
            "formats/blockquote/line": BlockquoteLineBlot,
            "formats/blockquote/content": BlockquoteContentBlot,
            "formats/blockquote/wrapper": BlockquoteWrapperBlot,
            "formats/spoiler/line": SpoilerLineBlot,
            "formats/spoiler/content": SpoilerContentBlot,
            "formats/spoiler/wrapper": SpoilerWrapperBlot,
            "formats/codeBlock": CodeBlockBlot,
            "formats/header": HeaderBlot,
            "formats/list/unordedGroup": UnorderedListGroup,
            "formats/list/orderedGroup": OrderedListGroup,
            "formats/list/item": ListItem,
            "formats/list/wrapper": ListItemWrapper,
            "formats/embed-error": EmbedErrorBlot,
            "formats/embed-loading": LoadingBlot,
            "formats/embed-external": ExternalEmbedBlot,
            "formats/mention": MentionBlot,
            "formats/mention-combobox": MentionComboBoxBlot,
            "formats/mention-autocomplete": MentionAutoCompleteBlot,

            // Inline formats
            "formats/bold": Bold,
            "formats/codeInline": CodeInlineBlot,
            "formats/italic": Italic,
            "formats/link": Link,
            "formats/strike": Strike,
            "formats/emoji": EmojiBlot,

            // Other
            "formats/indent": Indent,
            "themes/vanilla": VanillaTheme,
            "modules/embed/insertion": EmbedInsertionModule,
            "modules/embed/focus": FocusModule,
            "modules/history": HistoryModule,
            "modules/clipboard": ClipboardModule,
            "modules/syntax": SyntaxModule,
        },
        true,
    );

    // Ensure the InlineCodeBlock is the outermost when formats are nested.
    const inlineFormatBlots = [
        "blots/inline",
        "formats/bold",
        "formats/italic",
        "formats/strike",
        "formats/link",
        "formats/codeInline",
    ];

    inlineFormatBlots.forEach(blotLookup => {
        const BlotClass = Quill.import(blotLookup);
        BlotClass.order = [...BlotClass.order, CodeInlineBlot.blotName];
    });

    // The inline blot needs its order changed
    const blotsAllowingMentionComboBox = [
        "blots/block",
        "formats/list/item",
        "formats/header",
        "formats/bold",
        "formats/italic",
        "formats/strike",
    ];

    blotsAllowingMentionComboBox.forEach(blotLookup => {
        const BlotClass = Quill.import(blotLookup);
        BlotClass.allowedChildren = [...BlotClass.allowedChildren, MentionComboBoxBlot];
    });
}
