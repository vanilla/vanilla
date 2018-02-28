import Quill from "quill/core";

// Quill Block Formats
import List, { ListItem } from 'quill/formats/list';
import Header from 'quill/formats/header';

// Quill Inline Formats
import Bold from 'quill/formats/bold';
import Italic from 'quill/formats/italic';
import Link from 'quill/formats/link';
import Strike from 'quill/formats/strike';

import EmojiBlot from "./blots/EmojiBlot.js";
import ImageBlot from "./blots/ImageBlot.js";
import EmbedErrorBlot from "./blots/EmbedErrorBlot.js";
import SpoilerLineBlot, { SpoilerWrapperBlot, SpoilerContentBlot, BlockCursor } from "./blots/SpoilerBlot.js";
import SpoilerButtonBlot from "./blots/SpoilerButtonBlot";
import CodeBlockBlot from "./blots/CodeBlockBlot.js";
import VideoBlot from "./blots/VideoBlot.js";
import LinkEmbedBlot from "./blots/LinkEmbed.js";
import EmbedLoadingBlot from "./blots/EmbedLoadingBlot.js";
import BlockquoteLineBlot, { BlockquoteWrapperBlot, BlockquoteContentBlot } from "./blots/BlockquoteBlot.js";
import CodeInlineBlot from "./formats/CodeInlineBlot.js";

// Other
import ScrollBlot from "./blots/ScrollBlot";
import { IndentClass as Indent } from "quill/formats/indent";
import QuillTheme from "./QuillTheme";

Quill.register({
    'blots/scroll': ScrollBlot,
    'blots/block-cursor': BlockCursor,

    // Block formats
    'formats/blockquote/line': BlockquoteLineBlot,
    'formats/blockquote/content': BlockquoteContentBlot,
    'formats/blockquote/wrapper': BlockquoteWrapperBlot,
    'formats/code-block': CodeBlockBlot,
    'formats/header': Header,
    'formats/list': List,
    'formats/list/item': ListItem,
    'formats/image-embed': ImageBlot,
    'formats/spoiler/line': SpoilerLineBlot,
    'formats/spoiler/content': SpoilerContentBlot,
    'formats/spoiler/wrapper': SpoilerWrapperBlot,
    'formats/spoiler/button': SpoilerButtonBlot,
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
    'themes/vanilla': QuillTheme,
}, true);

export default Quill;
