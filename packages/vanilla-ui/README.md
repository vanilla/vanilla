# Vanilla UI

This library describes standard components used in the dashboard.

## General Guidelines

The following guidelines should be followed when creating components in this package.

**Generic**

Instead of creating controls for specific situations, make sure your control can adapt to any use.

```tsx
// ❌  Renders a very specific control for very few usages
<LargeFruitSelector value={fruit} onChange={setFruit} />

// ✅  Renders a large dropdown with fruits as options
<ListBox size="large" value={fruit} onChange={setFruit}>
    <ListBox.Item value="apple">Apples</ListBox.Item>
    <ListBox.Item value="banana">Banana</ListBox.Item>
</ListBox>
```

**Declarative**

Whenever possible, use the declarative form when specifying values or different parts of the control.

```tsx
// ❌  Makes it hard to style subcomponents of the ListBox
<ListBox items={[{ value: "apple" }], { value: "banana" }} className="myClass" itemClassName="myItemClass" />

// ✅  Composition gives us more flexibility and readability.
<ListBox className="myClass">
    <ListBox.Item value="apple" className="myItemClass" />
    <ListBox.Item value="banana" className="myItemClass" />
</ListBox>
```

**Composable**

```tsx
// ❌  Overly complicates the ListBox component
<ComboBox apiFetchItems={{ url: "api/v2/fruits" }} />

// ✅  Gives the responsibility of fetching and searching fruits to another component.
<ApiItemFetcher url="api/v2/fruits" searchUrl="api/v2/fruits?q=%s">
    {(items, onSearch) => (
        <ComboBox onSearch={onSearch}>
            {items.map(({ value, label }) => (
                <ListBox.Item value={value}>{label}</ListBox.Item>
            ))}
        </ComboBox>
    )}
</ApiItemFetcher>
```

**Ready-To-Use**

All components should provide sane defaults for each property. With a minimal amount of confugration the component should still render properly.

```tsx
// ❌  Unnecessary complexity when we want to quickly prototype a view.
<ListBox /> // Property 'value' is missing in type...
<ListBox value="" />

// ✅  Is valid and renders a standard listbox with no items.
<ListBox />
```

**Styled**

Components should provide their own styles by default but make it possible to override those styles if necessary. Styles for components should be declared in a file named `ComponentName.styles.ts`

**Documented**

Stories should document every component and it's options
