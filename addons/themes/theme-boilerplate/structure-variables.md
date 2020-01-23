## SCSS Folder Structure

### Base

Styles defining the scaffold of the theme. You can use this folder to import font families, define typography, mixins.

### Components

Components are self contained pieces of UI. They can be very small, like a breadcrumb or a button. They can also be complex and composed of other components like the advanced search control. Each component must have a unique class. They should also get a SASS partial with the same name.

Variables specific to a component should be defined on the top of the component's `.scss` file with a proper default and should only be used for that component.

### Sections

Sections are present in almost every page. For example the main header. Variables to be used in a section should be on the `_variables.scss` file since those variables may be inherited by a component.

Styles on this folder can overwrite components.

### Pages

Styles inside this folder should only be effective on a particular page. For example, you may want to add different styles specific to the profile page. In this case, you create a file `_profile.scss` and start your scss like this:

```
.Section-Profile {
    ...your styles here...
}
```

This way, styles described in the file above will only affect the layout on the profile page.

Styles on this folder can overwrite components and sections.

## Naming Convention

We're following a similar naming convention for the variables and the class names. This allows for quick and easy search and replace, since the variable names and css classes match. Also makes it really easy to know where it's supposed to be used. Camel case is used, like we do for the class names.

We want to go from generic to specific. We start with the element it styles, followed by the sub-element and the property.

```
{block}-{sub element (optionnal)}_{state (optionnal)}_{property}
```

Examples:

```
$vanillaBox-icon_padding
$input_height
```

What about more abstract styles that are applied to multiple blocks? Use the block name "global".

```
$global-button_paddingTop
```

What about states? Append "_hover" after the sub element.

```
$vanillaBox-icon_hover_paddingTop
```

## Variables Description

### Global

- **$global-body_fontFamily**: Main font family. Used pretty much for every text on the theme.
- **$global-body_fontWeight**: Same as above but for font weight.
- **$global-medium_fontSize**: Same as above but for font size.
- **$global-color_primary**: Your brand's primary color. (Recommended to have good contrast with `$global-color_bg`)
- **$global-color_primaryAlt**: A variation of the primary color, usually used on hover state (it might be a darker version of the same color like for example).
- **$global-color_secondary**: Your brand's secondary color. generally used for important call to actions, or hover/focus color. Recommended to have good contrast with `$global-color_bg`)
- **$global-color_bg**: Main color used for background. Adding a dark color to this variable will transform your theme into a dark theme, so make sure the `$global-color_fg` has high contrast with the color declared here.
- **$global-color_fg**: Main color used for foreground elements like text, icons, etc. Should have high contrast between `$global-color_bg`.

### Utility

- **$utility-baseUnitDouble**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-baseUnitHalf**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-baseUnitHalf**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-baseUnitTriple**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-xLarge_padding**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.

### Theme

- **$theme-link_color**: Color used on links.
- **$theme-link-hover_color**: Color used on links, but on hover state.
- **$theme-link-hover_textDecoration**: Value to be used to decorate links on hover state. Usually it's set to `none` or `underline`.

### Frame

- **$frame_backgroundColor**: Color used for background inside the theme frame.
- **$frame_backgroundImage**: Main background image. You can use this value to blend with `$frame_backgroundColor`.

### Header

- **$header_bg**: Background color for the header section.
- **$header_border**: Bottom border for the header section. To remove the border set this value to `none`.

### Panel

- **$panel_toLeft**: Set `true` on this variable if you want the panel to be on the left.
- **$panel_width**: Width for the panel section.
- **$panel_bg**: Background color for the panel section.
- **$panel_item-border**: Border for each item on the panel.
- **$panel_item-spacing**: Spacing between items on the panel. If the value of this field is `0`, item's borders will colapse.
- **$panel_item-borderRadius**: Border radius for each item on the panel.

### Footer

- **$footer_bg**: Background color for the footer section.
- **$footer_color**: Text color for the footer section.
- **footer-link_color**: Color used in links on the footer section.
- **$footer-link-hover_color**: Color used in links on the footer section, but on hover state.

### Component

- **$component-item_spacing**: Space between items. Generally used on categories and discussions lists. If the value of this variable is `0`, the items will collapse. 
- **$component_bg**: Background color used on items.
- **$component_borderWidth**: Border width used on items. Border's will always be solid. Seti this value to `0` to remove the border.
- **$component_borderColor**: Color used on items borders.
- **$component_boxShadow**: Box shadow used on items. If `$component-item_spacing` value is `0`, this variable will be used on the list, not on the items.
- **$component_borderRadius**: Box radius used on items. If `$component-item_spacing` value is `0`, this variable will be used on the list, not on the items.

### Form Element

- **$formElement_borderColor**: Border color for form inputs and textarea.
- **$formElement_borderRadius**: Border radius for form inputs and textarea.

### Form Button

- **$formButton_bg**: Background color used on buttons.
- **$formButton_color**: Text color used on buttons.
- **$formButton_borderRadius**: Border radius used on buttons. If you set this value to the `$formButton_height` , buttons will be round.



>  **Notice:** Some variables relative to specific components are not inside the `_variables.scss` file, those you can find inside the component `.scss` itself. Fell free to dig inside  `src/scss/components` to find out more.
