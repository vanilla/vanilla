# Vanilla UI

This library describes standard components used in the dashboard.

## Design goals

-   Easy to use: Be able to declare a component with a minimum of properties and quickly iterate work
-   Accessible: All of the components should be accessible
-   Styled: Every component should have a style making it instantly usable
-   Generic: Components should serve a broad purpose

## General Guidelines

The following guidelines should be followed when creating components in this package.

**Generic components can be used anywhere**

Instead of creating controls for specific situations, make sure your control can adapt to any use.

```tsx
// ❌  We can only select fruits with this? Nonsense
<LargeFruitSelector value={fruit} onChange={setFruit} />

// ✅  Renders a large dropdown with fruits as options
<ListBox size="large" value={fruit} onChange={setFruit}>
    <ListBoxItem value="apple">Apples</ListBoxItem>
    <ListBoxItem value="banana">Banana</ListBoxItem>
</ListBox>
```

**Composability over adding properties**

Whenever possible, it is always preferable to create a higher-order component or a hook to customize an existing component, as opposed to adding properties and unnecessary complexity.

```tsx
// ❌  Too many properties hide the real purpose of AutoComplete
<AutoComplete apiFetchItems={{ url: "api/v2/fruits" }} />

// ✅  Gives the responsibility of fetching and searching fruits to another component.
<ApiItemFetcher url="api/v2/fruits" searchUrl="api/v2/fruits?q=%s">
    {(items, onSearch) => (
        <AutoComplete onSearch={onSearch} options={items.map(({ value, label }) => ({
            value,
            label
        }))} />
    )}
</ApiItemFetcher>
```

**Ready-To-Use**

All components should provide sane defaults for each property. With a minimal amount of configuration the component should still render properly.

This makes it possible to quickly iterate an interface and add features incrementally.

```tsx
// ❌  Unnecessary complexity when we want to quickly prototype a view.
<ListBox /> // Property 'value' is missing in type...
<ListBox value="" />

// ✅  Is valid and renders a standard listbox with no items.
<ListBox />
```

**Styled by default**

Components should provide their own styles by default but make it possible to override those styles if necessary. Styles for components should be declared in a file named `ComponentName.styles.ts`

**Documented through storybook**

Stories should document every component and it's options
