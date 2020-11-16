# Rich Editor (WIP)

A highly-functional Rich Text editor for Vanilla built on top of [contenteditable](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Editable_content) using [Quill.js](https://quilljs.com/), [Quill's document model parchment](https://github.com/quilljs/parchment) and [React](https://reactjs.org/).

## Core features

-   Rich editing experience (similar to Medium editor).
-   Rich text embeds of external and internal content.
-   Improved video and image embedding.
-   Intuitive minimal design.
-   Opinionated restrictions on functionality.
-   Consistent serialized format that can be easily parsed on the server and client (JSON).
-   Convert all existing Advanced Editor formats (HTML/Wysiwyg, Markdown, BBCode, Text, TextEx).
-   Extendable to support future embeddable objects.

## Formatting

There are two main types of formatting: inline and block. Inline formatting applies to text within a paragraph while block formatting applies to entire paragraphs.

The MODIFIER-KEY varies by platform.

**MacOS** - CMD (⌘)
**Windows** - CTRL
**LINUX** - CTRL

| Format  | Type   | Applied With              | Notes                                                                                               |
| ------- | ------ | ------------------------- | --------------------------------------------------------------------------------------------------- |
| Bold    | Inline | Button, MODIFIER-KEY- + b |
| Italic  | Inline | Button, MODIFIER-KEY- + i |
| Code    | Inline | Button                    | Code has both inline and block versions.                                                            |
| Strike  | Inline | Button                    |
| Link    | Inline | Button, MODIFIER-KEY- + k |
| H1, H2  | Block  | Button                    | Two levels of heading are supported.                                                                |
| Bullets | Block  | Auto                      | A bulleted list automatically starts when you start a paragraph with "-" or "\*"                    |
| Numbers | Block  | Auto                      | A numbered list automatically starts when you start a paragraph with "1." or any number plus a ".". |
| Quote   | Block  | Button                    |
| Code    | Block  | Button                    | Code has both inline and block versions.                                                            |
| Spoiler | Block  | Button                    |

### Intentionally omitted formats

-   Underline
-   Font family
-   Text color
-   Text alignment

### Future supported formats

-   Tables

## Project Structure

This plugin is primarily javascript based. The main entrypoint for the application is [src/scripts/app/rich-editor.js](./src/scripts/app/rich-editor.js)

The [`RichEditor` class](./src/scripts/RichEditor.js) is responsible for a single editor instance. It uses our extended [`Quill` class](./src/scripts/Quill.js) which loads all of our custom Blots from [/blots](./src/scripts/blots) and [/formats](.src/scripts/formats).

A custom [Quill theme](./src/scripts/QuillTheme.jsx) is responsible for mounting the various React components that make up the editor's UI. These components can be found in [/components](./src/scripts/components) and primarily consist of

-   An emoji picker.
-   Block and inline formatting toolbars.

## Custom Quill Blots

### What is a Blot?

A Blot is javascript class used as part of Quill's (and its underlying library Parchment's) in memory document format. Quill uses LinkedLists for its in-memory data structure. This is different from Quill's persistent [Delta JSON format](https://quilljs.com/guides/designing-the-delta-format/).

Every Blot has reference to:

-   Its parent `.parent`
-   Its children `.children`
-   Its direct siblings `.prev`, `.next`
-   Its domNode `domNode`
-   The top level parent (a `ScrollBlot`) `.scroll`.

### Existing Blots

Existing Blots will generally be imported from Quill, but could also be imported from Parchment or the rich editor. The most common Blots to extend are `Block`, `Inline`, `Embed`, `BlockEmbed`, and `Container`. Some custom Blots provided are `WrapperBlot`, `ContentBlot`, and `LineBlot`,

**Block**

Equivalent to a paragraph. A line break signifies the end of a Block unless `white-space: pre` is set, in which case line breaks will be preserved. This is handled in the `CodeBlockBlot`.

**Inline**

Represents an text containing element. See [quill/formats](https://github.com/quilljs/quill/tree/master/formats) for examples of these (Bold, Italic, Strike).

**BlockEmbed** and **Embed**

Represents an element which is **not** contenteditable. Quill by default has no control or knowledge of the contents of these Blots. Embeds have a length of 1 and get deleted all at once unless special handling is introduced. Embeds can store arbitrary data in their delta/persistent data-structure by implementing the `static value()` function.

The BlockEmbed behaves like a Block element and generally cannot have siblings within its container and it's top level DOM element **must** have `display: block`. Look at the LinkEmbedBlot or the ImageBlot for an example.

The normal Embed is meant to represent an inline embed. The contents you assign in the `create()` method are get wrapped in a `span` and some guard text (0-width whitespace). Look at the EmojiBlot for an example.

**Container**

A container has the length of its children. Think of it like a DIV DOM Element. It is meant to wrap other elements. Extra work is required to get these to work because the are not representable in the final JSON data-structure. They must be created by their children. See the WrapperBlot for an example.

### Custom multi-line Blots with containers

Quill encourages a flattened HTML structure but this is not always possible. To implement structures like the following:

```html
<blockquote>
    <div class="blockquote-content">
        <p>Line 1</p>
        <p>Line 2</p>
    </div>
</blockquote>
```

Use the `Wrapperblot`, `ContentBlot`, and `LineBlot`. See the Blockquote for an example. You will need to implement and register all 3 blots. The whole is actually created form the `LineBlot` upwards. The `LineBlot` then creates it's parents. This does not work in the other direction.

Both the LineBlot and the ContentBlot must implement a `static ParentName` in addition to their `blotName` and `className`.

See the Blockquote or Spoiler for an example.

### How to define a new Blot

1.  Extend an existing Blot.

```js
import Block, { BlockEmbed } from "quill/blots/block";
import Inline from "quill/blots/inline";
import Embed from "quill/blots/embed";

// Custom Blots
import WrapperBlot, { ContentBlot, LineBlot } from "@rich-editor/blots/WrapperBlot";

export default class MyCustomBlot extends OneOfTheImportedClasses {
    static blotName = "my-custom-blot";
    static tagName = "div";
    static className = "this-blots-css-class";
}
```

See [differentiating blots]("#differentiating-blots) for more information about these properties.

2.  Register the Blot in our [Quill instance]("./src/scripts/Quill.js") with a unique id. This is ID is only used for Quill's registration and overriding existing Blots, and is not used for instantiating the Blot.

3.  Instantiate the Blot using it's `blotName`.

```js
import parchment from "parchment";

// Using parchment and the blotName
const newBlot = parchment.create(MyCustomBlot.blotName);

// Insert the blot somewhere
newBlot.insertInto(parentBlot, referenceBlot);
parentBlot.insertBefore(newBlot, referenceBlot);
parentBlot.appendChild(newBlot);
someOtherBlot.replaceWith(newBlot);
newBlot.replace(someOtherBlot);
```

### Differentiating Blots

There are a few requirements for custom Blots so that everything works properly.

-   Blots must have a unique `static blotName`.
-   Blots must have a unique `static tagName` or a unique `static className` or implement a unique `formats(): Object` and `static formats(): boolean`.

### Lifecycle of a Blot.

1.  `static create(): Node`

This static function generates a DOM Node for the Blot. It will generate a DOM Node with the provided `tagName` and `className`. This is not a good place to set listeners because you can't save a reference to them anywhere to remove them. _Don't call this function directly. Use `parchment.create(blotName): Blot` instead_.

```js
class CustomBlot extends Block {
    static create() {
        const node = super.create();

        // Do things with the DOM Node in a static context

        return node;
    }
}
```

2.  `constructor(domNode)`

The constructor gets passed the DOM Node created by the create() function. The Blot still has not been connected to it's parent and siblings at the time of construction and it (as well its DOM Node) have not actually been attached anywhere.

```js
class CustomBlot extends Block {
    constructor(node) {
        super(node);

        // Do things with the DOM Node in a class context. Save anything you need a reference to.
    }
}
```

3.  `attach(): void`

Attaches the Blot to it's parent and siblings. Inserts the Blot's DOM Node into the document under it's parent. _Don't call this function directly. Use `parchment.create(blotName): Blot` and one of the various insert functions instead_.

```js
class CustomBlot extends Block {
    attach() {
        super.attach();

        // Do something that requires the parent or sibling Blots, or for the Blot to actually be in the document.
    }
}
```

4.  `detach(): void`

The opposite of attach. Do any cleanup from that here.

5.  `remove(): void`

Deletes the Blot and it's DOM Node. Put any cleanup from `static create()` or the constructor here.

### Some Additional Notes

The following excellent links should be consulted for initia:

-   https://dev.to/charrondev/getting-to-know-quilljs---part-1-parchment-blots-and-lifecycle--3e76

-   https://quilljs.com/docs/quickstart/

### Quill formats

Each document as seen in the browser is represented by a DOM, but for the
purpose of manipulating editting process Quill uses two additional structures,
**Blots** and **Deltas**:

```
     +---------+
     |         |
     |   doc   |
     |         |
     +---------+
          |
          |
          |
          v
        DOM
         ^
         |
         v
        BLOTS
         ^
         |
         |
         v
        DELTA
```

Blots is a tree-like structure that is used in place of the DOM. However, the
changes that happen in a document are described using DELTAS, a sequence of
low-level operations, for example

```json
{ "insert": "Block operations H2 Title here. Code Block next." },
    { "attributes": { "header": { "level": 2, "ref": "testRef"} }, "insert": "\n" },
    { "insert": "/**" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": " *adds locale data to the view, and adds a respond button to the discussion page." },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": " */" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "class MyThemeNameThemeHooks extends Gdn_Plugin {" },
    { "attributes": { "codeBlock": true }, "insert": "\n\n" },
    { "insert": "    /**" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     * Fetches the current locale and sets the data for the theme view." },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     * Render the locale in a smarty template using {$locale}" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     *" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     * @param  Controller $sender The sending controller object." },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    { "insert": "     */" },
    { "attributes": { "codeBlock": true }, "insert": "\n" },
    {
        "insert":
        "    public function base_render_beforebase_render_beforebase_render_beforebase_render_beforebase_render_before($sender) {"
    },
```

So, a document is just a blank document with an appropriate sequence of deltas
applied to it. To see the methods that can be used on deltas, please check the file
`quill.d.ts`.

### Modules

Quill is organized as a core extended with modules, and a set of APIs for
changing and extending modules or even writing new ones. To get additional
details on how to build a custom module, read the link:

https://quilljs.com/guides/building-a-custom-module/

To see how to go from a basic editor to a substantial one, consult the link

https://quilljs.com/guides/cloning-medium-with-parchment/

#### Clipboard

An intesting and important module is `Clipboard`
(https://quilljs.com/docs/modules/clipboard/) that controls copy-paste
behaviour. Since a copy-paste leads to an immediate change in the document (we
can do it manually using the provided method `dangerouslyPasteHTML`) , HTML code
is parsed directly into deltas and applied to the document. An interesting note:
to intercept the parsing process (to modify the deltas), checkout the method
`convert`.

It's important to keep in mind that deltas simply describes what changes
are to be made to a document. To apply the changes to the document, e.g.
at an appropriate place, we use the methods provided by Blots.

#### ListBlot

The bug fix mentioned earlier involves working with the (complicated) `ListBlot`.
This module handles working with lists, including nested lists. To control
the insertion of an image into a list, for example, work with the method
`insertAt`.
