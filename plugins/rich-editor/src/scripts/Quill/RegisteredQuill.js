/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill Built-ins
import Quill from "quill/core";
import Bold from 'quill/formats/bold';
import Italic from 'quill/formats/italic';
import Link from 'quill/formats/link';
import Strike from 'quill/formats/strike';
import List, { ListItem } from 'quill/formats/list';
import Header from 'quill/formats/header';
import { IndentClass as Indent } from "quill/formats/indent";

// Custom Inline Blots
import CodeInlineBlot from "./Blots/Inline/CodeBlot";

// Custom Embed Blots
import EmojiBlot from "./Blots/Embeds/EmojiBlot";
import ImageBlot from "./Blots/Embeds/ImageBlot";
import VideoBlot from "./Blots/Embeds/VideoBlot";
import LinkEmbedBlot from "./Blots/Embeds/RichLinkBlot";
import EmbedLoadingBlot from "./Blots/Embeds/LoadingBlot";
import EmbedErrorBlot from "./Blots/Embeds/ErrorBlot";

// Custom Block Blots
import SpoilerLineBlot, { SpoilerWrapperBlot, SpoilerContentBlot } from "./Blots/Blocks/SpoilerBlot";
import BlockquoteLineBlot, { BlockquoteWrapperBlot, BlockquoteContentBlot } from "./Blots/Blocks/BlockquoteBlot";
import CodeBlockBlot from "./Blots/Blocks/CodeBlockBlot";

// Custom Modules/Themes
import VanillaTheme from "./VanillaTheme";
import EmbedInsertionModule from "./EmbedInsertionModule";

Quill.register({
    // Block formats
    'formats/blockquote/line': BlockquoteLineBlot,
    'formats/blockquote/content': BlockquoteContentBlot,
    'formats/blockquote/wrapper': BlockquoteWrapperBlot,
    'formats/spoiler/line': SpoilerLineBlot,
    'formats/spoiler/content': SpoilerContentBlot,
    'formats/spoiler/wrapper': SpoilerWrapperBlot,
    'formats/code-block': CodeBlockBlot,
    'formats/header': Header,
    'formats/list': List,
    'formats/list/item': ListItem,
    'formats/image-embed': ImageBlot,
    'formats/video-placeholder': VideoBlot,
    'formats/loading-embed': EmbedLoadingBlot,
    'formats/link-embed': LinkEmbedBlot,
    'formats/error-embed': EmbedErrorBlot,

    // Inline formats
    'formats/bold': Bold,
    'formats/code-inline': CodeInlineBlot,
    'formats/italic': Italic,
    'formats/link': Link,
    'formats/strike': Strike,
    'formats/emoji': EmojiBlot,

    // Other
    'formats/indent': Indent,
    'themes/vanilla': VanillaTheme,
    'modules/embed/insertion': EmbedInsertionModule,
}, true);

export default Quill;
