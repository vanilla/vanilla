## Folder Structure

### Base

Styles defining the scaffold of your theme. You can use this folder to import font families, typography, or define mixins, for example.

### Components

Components are self-contained pieces of code. Styles defined on one component should not be inherited on another.

Variables specific to a component should be defined on the top of the component's `.scss` file with a proper default and should only be used for that component.

### Sections

Sections are pieces present in almost every page. For example the header. Variables to be used in a section should be on the `_variables.scss` file since those variables may be inherited by a component.

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

- **$global-body_fontFamily**: Main font family. Used on pretty much for every text on the theme.
- **$global-body_fontWeight**: Same as above but for font weight.
- **$global-medium_fontSize**: Same as above but for font size.
- **$global-color_primary**: Your brand's primary color
- **$global-color_primaryAlt**: A variation of the primary color, usually used on hover state. It might be a darker version of the same color like for example `darken($global-color_primary, 8%);`.
- **$global-color_secondary**: Your brand's secondary color
- **$global-color_bg**: Main color used for background. Adding a dark color to this variable will transform your theme into a dark theme, so make sure the `$global-color_fg` has high contrast with the color declared here.
- **$global-color_fg**: Main color used for foreground elements like text, icons, etc. Should have high contrast between `$global-color_bg`.



- **$utility-medium_padding**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-xSmall_padding**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-small_padding**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-large_padding**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.
- **$utility-xLarge_padding**: Utility variables are heavily inherited through the theme. Editing those variables may break your layout, please do not edit them.



- **$theme-link_color**: Color to be used on links.
- **$theme-link-hover_color**: Color to be used on links, but on hover state.
- **$theme-link-hover_textDecoration**: Value to be used to decorate links on hover state. Usually it's set to `none` or `underline`.



- **$frame_backgroundColor**: Main background color.
- **$frame_backgroundImage**: Main background image. You can use it to blend with the background color.



- **$header_bg**: Background color for the header section.
- **$header_border**: Bottom border for the header section. To remove the border set this value to `none`.



- **$panel_toLeft**: Set `true` on this variable if you want the panel to be on the left.
- **$panel_width**: Width for the panel section.
- **$panel_bg**: Background color for the panel section.



- **$footer_bg**: Background color for the footer section.
- **$footer_color**: Text color for the footer section.
- **footer-link_color**: Color used in links on the footer section.
- **$footer-link-hover_color**: Color used in links on the footer section, but on hover state.



- **$component-item_spacing**: Space between items. Generally used on categories and discussions lists. If the value of this variable is `0`, the items will collapse. 
- **$component_bg**: Background color used on items.
- **$component_borderWidth**: Border width used on items. Border's will always be solid. Seti this value to `0` to remove the border.
- **$component_borderColor**: Color used on items borders.
- **$component_boxShadow**: Box shadow used on items. If `$component-item_spacing` value is `0`, this variable will be used on the list, not on the items.
- **$component_borderRadius**: Box radius used on items. If `$component-item_spacing` value is `0`, this variable will be used on the list, not on the items.



- **$formElement_borderColor**: Border color for form inputs and textarea.
- **$formElement_borderRadius**: Border radius for form inputs and textarea.



- **$formButton_bg**: Background color used on buttons.
- **$formButton_color**: Text color used on buttons.
- **$formButton_borderRadius**: Border radius used on buttons. If you set this value to the `$formButton_height` , buttons will be round.



>  **Notice:** Some variables relative to specific components are not inside the `_variables.scss` file, those you can find inside the component `.scss` itself. Fell free to dig inside  `src/scss/components` to find out more.

