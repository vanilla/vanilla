# List Blots

The list blots are quite complicated. This README serves as some documentation of their different
representations and behaviours.

## Simple Example

Here's how the following canonical HTML form is represented in quill.

```html
<ul>
    <li><p>Line 1</p></li>
    <li><p>Line 2</p></li>
</ul>
```

### Quill Delta

A list line is made up of:

-   1 or more inserts **_without a newline_** (line terminator)
-   A line terminator with a `list` attribute. (One or multiple newline characters)
    -   Attribute has the list depth and type.

```json
[
    {
        "insert": "Line 1",
        "attributes": {
            "bold": true
        }
    },
    {
        "insert": "\n",
        "attributes": {
            "list": {
                "depth": 0,
                "type": "bullet"
            }
        }
    },
    {
        "insert": "Line 2",
        "attributes": {
            "bold": true
        }
    },
    {
        "insert": "\n",
        "attributes": {
            "list": {
                "depth": 0,
                "type": "bullet"
            }
        }
    }
]
```

### Blot Structure (Canonical)

This is how the blot structure is organized after all optimization passes.

```
UnorderedListGroupBlot (<ul>)
  -> ListItemWrapperBlot (<li>)
     -> ListLineBlot (<p>)
  -> ListItemWrapperBlot (<li>)
     -> ListLineBlot (<p>)
```

### Blot Structure (On Insertion)

Quill doesn't directly underestand grouping things in wrapped parents, so here's how it works.

Just keep in mind the mappings listed up above as this will refer to the HTML representations.

1. Quill will attempt to insert a delta (through `quill.updateContents(...delta)`)

-   If HTML was given (such as through pasting), then it will be converted to a delta.
-   These facilities are entirely provided by quill.

2. The following structure will be created.

```html
<p>Line 1</p>
<p>Line 2</p>
```

3. Multiple optimization passes occur. Quill optimizes from the inside out.

    The key principle to remember with optimization: Optimization is really just canonicalizing the HTML so there is only 1 final representation of some delta.

    Keep in mind there are various intermediate states the editor has to deal with that can arise from various situations. For example if an element is deleted and 2 list items or groups are now next to each other.

#### Optimization Passes

##### 1. Inline content is optimized.

There could be inline content in a list item that needs to be optimized.

For example

```html
<strong>hello</strong><strong> world</strong>
// Becomes
<strong>hello world</strong>
```

##### 2. `ListLineBlot` blot is optimized

The line blots will automatically create a wrapper (`ListItemWrapperBlot`) if it does not exist.

Our HTML and blot structure should now match this.

```html
<li><p>Line 1</p></li>
<li><p>Line 2</p></li>
```

##### 3. `ListItemWrapperBlot` is optimized.

First the wrapper will do some nesting optimization. See below in [Nested List Optimization Passes](#nested-list-optimization-passes).

Next the wrapper blot validates it's children.

1. If there are no children, it will remove itself (as it's just useless HTML).
2. If there are any children unrelated that aren't `ListGroupBlot`, they will get wrapped up inside of a new `ListLineBlot`. This is to handle some of those "intermediate states" mentioned up above.

Next standard wrapper blot optimization occurs:

-   If the wrapper is empty at this point, (eg no children, no line inside, no nested list inside), it will remove itself. It is just useless, non-canonical HTML. This commonly occurs when deleting a line (once the last line is removed, the wrapper is no longer necessary).

    _Keep in mind that the `ListItemWrapperBlot` and `ListGroupBlot` variants do not have any **data** representing them. They are purely for visual organization._

-   The wrappers will automatically create their own wrapper if it does not exist.
    This will be one of the `ListGroupBlot` variants.

Our HTML and blot structure should now match this.

```html
<ul>
    <li><p>Line 1</p></li>
</ul>
<ul>
    <li><p>Line 2</p></li>
</ul>
```

##### 4. `ListGroupBlot` is optimized.

First the group will do some nesting optimization. See below in [Nested List Optimization Passes](#nested-list-optimization-passes).

Next the group will attempt to merge related groups together. If there are 2 of the same type of `ListGroup` directly after each other, the earlier group(s) will merge the wrappers from the following ones inside of themselves.

We've now arrived at the canonical representation for this simple list.

Our HTML and blot structure should now match this.

````html
<ul>
    <li><p>Line 1</p></li>
    <li><p>Line 2</p></li>
</ul>

Additionally, the group performs a similar check to the wrapper and will remove itself it has no children. ## Nested
Lists Nested lists (eg. Lists nested inside of each other) are complicated. ### Quill Delta Here's an example of the
delta representation of a nested list. There are a few noteworthy things here: - The delta representation, like all
other deltas, is flat. - Depth of a list item is represented by a "depth" parameter. ```json [ { "insert": "Unordered
List Line 1" }, { "insert": "\n", "attributes": { "list": { "depth": 0, "type": "bullet" } } }, { "insert": "Ordered
List Line 1.1" }, { "insert": "\n", "attributes": { "list": { "depth": 1, "type": "ordered" } } } ]
````

### Nested List Optimization Passes

In order to actually transform this delta into a nested blot structure and nested HTML, a simple flat HTML structure is created, and then optimized into a nested one.

This delta will start out as the following HTML

```html
<p data-depth="0" data-type="bulleted">Unordered List Line 1</p>
<p data-depth="1" data-type="ordered">Ordered List Line 1</p>
```

After the standard set of optimizaton passes you would end up with this

```html
<ul>
    <li>
        <p data-depth="0" data-type="bulleted">Unordered List Line 1</p>
    </li>
</ul>
<ol>
    <li>
        <p data-depth="1" data-type="ordered">Ordered List Line 1</p>
    </li>
</ol>
```

This definitely isn't what we want so additional optimization occurs in the places noted in the standard optimization notes. Here's the extra steps:

_Again, remember that optimization in quill typically occurs from the inside to the outside._

#### `ListItemWrapperBlot` Nested optimizations

The list item wrapper is capable of holding 1 single list group inside of it (after the main content).

Each wrapper will do the following:

-   Look at it's previous sibling (if there is one). If "we" are of a higher depth than the previous item we will move ourselves into it.
-   This is done by calling `addNestedChild(us)` on the previous wrapper with ourself as the parameter.

    This method creates a new list group of the appropriate type if it does not already exist at the end of the wrapper to hold the new child.

#### Group Nested Optimizations

**Nesting Groups**

In addition to the merging groups of the same type and level together, a group will look at it's next sibling and see if it's deeper.
If the next sibling is of a higher depth than itself, then it will find it's last list wrapper child, and move that group into the wrapper.

**Unnesting Groups**

Additionally groups kick off a slightly complicated method of unnesting items at the wrong depth.

As stated before, quill normally optimized from the inside out
but this can break ordering if we don't know where to put things on their way out if the list is split in half.

_Keep in mind that when items are unnested, they are placed after the current list wrapper._

Check this example:

```
// Starting point

- Item 1 - (Depth 0)
  - Item 2 (Depth 1)
  - Item 3 (Depth 1)
  - Item 4 (Depth 1)
    - Item 5 (Depth 2)

// User changes item 2 to be a different format.

- Item 1 - (Depth 0)

> Item 2

  - Item 3 (Depth 0)
  - Item 4 (Depth 0)
    - Item 5 (Depth 1)
```

Let's see how that second split off list group would optimize itself with 2 different methods.

**Deeper Elements first (Quill default)**

```
  - Item 3 (Depth 0)
  - Item 4 (Depth 0)
    - Item 5 (Depth 1)

// Pass 1

  - Item 3 (Depth 0)
  - Item 4 (Depth 0)
  - Item 5 (Depth 1)

// Pass 2

  - Item 4 (Depth 0)
  - Item 5 (Depth 1)
- Item 3 (Depth 0)

// Pass 3

  - Item 5 (Depth 1)
- Item 3 (Depth 0)
- Item 4 (Depth 0)
```

This definitely didn't work.

**Outside Elements first (Custom implementation)**

We use a custom implementation, where the top-level list will manually trigger the unnesting from the outside in.

This preserves list ordering and is more efficient as well.

```
  - Item 3 (Depth 0)
  - Item 4 (Depth 0)
    - Item 5 (Depth 1)

// Pass 1

- Item 3 (Depth 0)
  - Item 4 (Depth 0)
    - Item 5 (Depth 1)

// Pass 2

- Item 3 (Depth 0)
- Item 4 (Depth 0)
  - Item 5 (Depth 1)
```

Because item 5 is contained in item 4, we they both came together when moving item 4 up. We even saved 1 optimization pass!

## How a user triggers nesting and unnesting.

In all situations, when a user adjusts the indentation of an item, the data is changed on the various affected items,
and then we let optimization fix the HTML and blot structure.

### Indentation Examples

If an item is "indented" it and all nested children will have their lines indented as well.

**Example**

```
- Item 1 (Depth 0)
- Item 2 (Depth 0)
  - Item 3 (Depth 1)
  - Item 4 (Depth 1)
    - Item 5 (Depth 1)

// Indent Item 2

- Item 1 (Depth 0)
- Item 2 (Depth 1)
  - Item 3 (Depth 2)
  - Item 4 (Depth 2)
    - Item 5 (Depth 3)

// After Optimization
- Item 1 (Depth 0)
  - Item 2 (Depth 1)
    - Item 3 (Depth 2)
    - Item 4 (Depth 2)
      - Item 5 (Depth 3)
```

There are a few gaurds on indentation

-   The max indentation is 4.
-   A child can only be indented if it has a previous sibling to be placed in it. From the example, the following lines cannot be indented.
    -   Item 1
    -   Item 3

### Outdentation Examples

Outdentation will reduce the indentation of a line and all of it's children by 1.
Additionally an optimization pass will move all of it's siblings inside of it.

```
- Item 1 (Depth 0)
  - Item 2 (Depth 1)
  - Item 3 (Depth 1)
    - Item 4 (Depth 2)
  - Item 5 (Depth 1)
    - Item 6 (Depth 2)

// Outdent lines 2 and 6

- Item 1 (Depth 0)
  - Item 2 (Depth 0)
  - Item 3 (Depth 1)
    - Item 4 (Depth 2)
  - Item 5 (Depth 0)
    - Item 6 (Depth 1)

// After optimization

- Item 1 (Depth 0)
- Item 2 (Depth 0)
  - Item 3 (Depth 1)
    - Item 4 (Depth 2)
- Item 5 (Depth 0)
  - Item 6 (Depth 1)
```

## Unhandled edge-cases

These cases should generally not occur in the real world, but you could manually construct a delta that looks like these. This behaviour is currently considered undefined.

### Gaps in lists

Eg like this.

```
Item Depth 1
Item Depth 3
```

### Multiple nested list groups

Here's ane example markdown representation.

```md
-   Item 1
    -   Nested 1.1
    -   Nested 1.2
    1. Nested ordered 1.1
    2. Nested ordered 1.2
```
