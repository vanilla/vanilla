/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill Built-ins
import Quill from "quill/core";
import Bold from "quill/formats/bold";
import Italic from "quill/formats/italic";
import Link from "quill/formats/link";
import Strike from "quill/formats/strike";
import List, { ListItem } from "quill/formats/list";
import Header from "quill/formats/header";
import { IndentClass as Indent } from "quill/formats/indent";

// Custom Inline Blots
import CodeInlineBlot from "./Blots/Inline/CodeBlot";

// Custom Embed Blots
import EmojiBlot from "./Blots/Embeds/EmojiBlot";
import ExternalEmbedBlot from "./Blots/Embeds/ExternalEmbedBlot";
import EmbedErrorBlot from "./Blots/Embeds/ErrorBlot";
import MentionBlot from "./Blots/Embeds/MentionBlot";
import MentionComboBoxBlot from "./Blots/Embeds/MentionComboBoxBlot";
import MentionAutoCompleteBlot from "./Blots/Embeds/MentionAutoCompleteBlot";

// Custom Block Blots
import BlockBlot from "./Blots/Blocks/BlockBlot";
import SpoilerLineBlot, { SpoilerWrapperBlot, SpoilerContentBlot } from "./Blots/Blocks/SpoilerBlot";
import BlockquoteLineBlot, { BlockquoteWrapperBlot, BlockquoteContentBlot } from "./Blots/Blocks/BlockquoteBlot";
import CodeBlockBlot from "./Blots/Blocks/CodeBlockBlot";

// Custom Modules/Themes
import VanillaTheme from "./VanillaTheme";
import EmbedFocusModule from "./EmbedFocusModule";
import EmbedInsertionModule from "./EmbedInsertionModule";
import HistoryModule from "./HistoryModule";
import LoadingBlot from "./Blots/Embeds/LoadingBlot";

Quill.register(
    {
        // Block formats
        "blots/block": BlockBlot,
        "formats/blockquote/line": BlockquoteLineBlot,
        "formats/blockquote/content": BlockquoteContentBlot,
        "formats/blockquote/wrapper": BlockquoteWrapperBlot,
        "formats/spoiler/line": SpoilerLineBlot,
        "formats/spoiler/content": SpoilerContentBlot,
        "formats/spoiler/wrapper": SpoilerWrapperBlot,
        "formats/code-block": CodeBlockBlot,
        "formats/header": Header,
        "formats/list": List,
        "formats/list/item": ListItem,
        "formats/embed-error": EmbedErrorBlot,
        "formats/embed-loading": LoadingBlot,
        "formats/embed-external": ExternalEmbedBlot,
        "formats/mention": MentionBlot,
        "formats/mention-combobox": MentionComboBoxBlot,
        "formats/mention-autocomplete": MentionAutoCompleteBlot,

        // Inline formats
        "formats/bold": Bold,
        "formats/code-inline": CodeInlineBlot,
        "formats/italic": Italic,
        "formats/link": Link,
        "formats/strike": Strike,
        "formats/emoji": EmojiBlot,

        // Other
        "formats/indent": Indent,
        "themes/vanilla": VanillaTheme,
        "modules/embed/insertion": EmbedInsertionModule,
        "modules/embed/focus": EmbedFocusModule,
        "modules/history": HistoryModule,
    },
    true,
);

const blotsAllowingMentionComboBox = ["blots/block", "formats/bold", "formats/italic", "formats/strike"];

blotsAllowingMentionComboBox.forEach(blotLookup => {
    const BlotClass = Quill.import(blotLookup);
    BlotClass.allowedChildren = [...BlotClass.allowedChildren, MentionComboBoxBlot];
});

export default Quill;
