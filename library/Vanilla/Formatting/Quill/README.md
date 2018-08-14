## Vanilla Quill Renderer (WIP)

_This renderer is not yet considered secure or suitable for production use._

This renderer allows rendering of a [Quill Delta](https://github.com/quilljs/delta), which is the format used by Vanilla's `rich-editor` addon. The delta is a JSON format which is strictly parsed and pieced together into HTML output. Currently no HTML input is allowed so filterers like HTMLawed are not necessary. All parsing of the Delta should be done in the renderer or one of the Blots. The outputted HTML should not be parsed again afterwards.

## Blot

In `Quill` a Blot maps to a DOM Node. When `Delta`s are rendered in the browser they need to be able to mutate and handle complex state changes. This server side renderer should be lighter and faster as it does not need to handle mutations. Instead Blots map to a particular type of editor format option. 

All Blots inherit from the `AbstractBlot` class.

### TextBlot

The `TextBlot` is a blot containing user inputted text. It as well as all its descendants (inheriting children) can contain formats, found under the `Formats` namespace. Multiple formats can be active at one time and all are nested inside of a wrapper (Generally a `<p>` tag).

These formats are bold, italic, strike-through, link, and inline code. The `TextBlot` is responsible for rendering these tags in the correct nesting order.

### LineTerminatorBlots

A text blot is an "inline" type of blot. Multiple can `TextBlots` and `InlineEmbedBlots` can be contained in a single
 line. In a delta a line is generally delineated by an insert with and insert of `\n` or multiple `\n\n\n` characters
 . A line blot terminator provides the wrapping tag for the line such as `<p>` `<li>` `<blockquote>` etc. If an 
 additional wrapping tag is need this should be provided by the group.
 
 A few implementations of `AbstractLineTerminatorBlot` include
 
 - `BlockquoteLineTerminatorBlot`
 - `ParagraphLineTerminatorBlot`
 - `ListLineTerminatorBlot` 

### Embed Blots

Embed blots contain user contain, but are not directly editable text content. There are two types of embed 
`ExternalBlot` and `AbstractInlineEmbedBlot`. The external blot uses the EmbedManager to render some external content
 in an embedded context.

These generally contain deeply nested and complex HTML structures. The inputs are nested JSON structures, none of which is currently validated on insert to the database, so be sure to carefully sanitize any information coming in here.

## Groups

A group is a concept that does have a direct concept in the client side renderer of `Quill`, but is used on the server to simplify rendering. A group is a representation of a single top level HTML element in the rendered output.

```html
<p>This is a group</p>
<p>Another group <strong>Bold</strong></p>
<blockquote>
...
...
Multiple lines is all one group.
...
</blockquote>
<ul>
... Multiple list elements are all one group.
<li></li>
...
</ul>
```

## Creating a new Blot

Creating a new blot similar to an existing one is easy. Extend either `TextBlot`, `AbstractBlockBlot`, `AbstractInlineEmbedBlot` or `AbstractBlockEmbedBlot`. These map pretty similar to the client-side `Text`, `Block`, `Embed`, and `BlockEmbed` blots respectively.
