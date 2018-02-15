import Quill from "quill/core";

// Quill Block Formats
import List, { ListItem } from 'quill/formats/list';
import Blockquote from 'quill/formats/blockquote';
import Header from 'quill/formats/header';
import CodeBlock, { Code as InlineCode } from 'quill/formats/code';

// Quill Inline Formats
import Bold from 'quill/formats/bold';
import Italic from 'quill/formats/italic';
import Link from 'quill/formats/link';
import Strike from 'quill/formats/strike';

// Other
import { IndentClass as Indent } from "quill/formats/indent";
import QuillTheme from "./QuillTheme";
import Emoji from "./blots/EmojiBlot";

Quill.register({
    // Block formats
    'formats/blockquote': Blockquote,
    'formats/code-block': CodeBlock,
    'formats/header': Header,
    'formats/list': List,
    'formats/list/item': ListItem,

    // Inline formats
    'formats/bold': Bold,
    'formats/code': InlineCode,
    'formats/italic': Italic,
    'formats/link': Link,
    'formats/strike': Strike,
    'formats/emoji': Emoji,

    // Other
    'formats/indent': Indent,
    'themes/vanilla': QuillTheme,
});

export default Quill;
