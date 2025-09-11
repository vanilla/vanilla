/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export const ColorVar = {
    Primary: "--vnla-primary-color",
    PrimaryContrast: "--vnla-primary-contrast-color",
    PrimaryState: "--vnla-primary-state-color",
    Secondary: "--vnla-secondary-color",
    SecondaryState: "--vnla-secondary-state-color",
    SecondaryContrast: "--vnla-secondary-contrast-color",
    Background: "--vnla-background-color",
    Background1: "--vnla-background1-color",
    Background2: "--vnla-background2-color",
    Foreground: "--vnla-foreground-color",
    Meta: "--vnla-meta-color",
    DropdownBackground: "--vnla-dropdown-background-color",
    DropdownForeground: "--vnla-dropdown-foreground-color",
    Border: "--vnla-border-color",
    HighlightBackground: "--vnla-highlight-background-color",
    HighlightForeground: "--vnla-highlight-foreground-color",
    HighlightFocusBackground: "--vnla-highlight-focus-background-color",
    HighlightFocusForeground: "--vnla-highlight-focus-foreground-color",
    InputBackground: "--vnla-input-background-color",
    InputForeground: "--vnla-input-foreground-color",
    InputBorder: "--vnla-input-border-color",
    InputBorderActive: "--vnla-input-border-active-color",
    InputPlaceholder: "--vnla-input-placeholder-color",
    InputTokenBackground: "--vnla-input-token-background-color",
    InputTokenForeground: "--vnla-input-token-foreground-color",
    ModalBackground: "--vnla-modal-background-color",
    ModalForeground: "--vnla-modal-foreground-color",
    Link: "--vnla-link-color",
    LinkActive: "--vnla-link-active-color",
    ComponentInnerSpace: "--vnla-component-inner-space",
    Yellow: "--vnla-yellow-color",
    Red: "--vnla-red-color",
    Green: "--vnla-green-color",
} as const;

export type ColorVar = (typeof ColorVar)[keyof typeof ColorVar];
