/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as i18n from "@vanilla/i18n";
import "@library/theming/reset";
import { default as DropDownItemButton } from "@library/flyouts/items/DropDownItemButton";
import { default as DropDownItemSeparator } from "@library/flyouts/items/DropDownItemSeparator";
import { default as DropDownItem } from "@library/flyouts/items/DropDownItem";
import { default as DropDownInit } from "@library/flyouts/DropDown";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import ModalInit from "@library/modal/Modal";
import { FramedModal } from "@library/modal/FramedModal";
import { MetaItem, MetaLink, Metas, MetaTag } from "@library/metas/Metas";
import { NestedSelect } from "@library/forms/nestedSelect";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";

export namespace Select {
    export type Option = NestedSelect.Option;
    export type OptionLookup = NestedSelect.OptionLookup;
    export type Config = NestedSelect.Config;
}
export const Select = NestedSelect;

export const Meta = {
    Item: MetaItem,
    Link: MetaLink,
    Tag: MetaTag,
    Root: Metas,
};

/// TODO:
/// Make sure pager is dark mode compatible.
///

export const Modal = Object.assign(ModalInit, {
    Framed: FramedModal,
});

// When using externally we don't need the translations.

export const DropDown = Object.assign(DropDownInit, {
    Item: DropDownItem,
    ItemButton: DropDownItemButton,
    ItemLink: DropDownItemLink,
    ItemSeparator: DropDownItemSeparator,
    ItemSwitch: DropDownSwitchButton,
    Section: DropDownSection,
});

// Vanilla re-exports
export { SchemaFormBuilder } from "@library/json-schema-forms";
export { LinkContext } from "@library/routing/links/LinkContextProvider";
export { DashboardSchemaForm as SchemaForm } from "@dashboard/forms/DashboardSchemaForm";
export { DashboardFormGroup as FormGroup } from "@dashboard/forms/DashboardFormGroup";
export { DashboardLabelType as LabelType } from "@dashboard/forms/DashboardLabelType";
export { DashboardInputWrap as InputWrap } from "@dashboard/forms/DashboardInputWrap";
export { TextInput } from "@library/forms/TextInput";
export { default as Button } from "@library/forms/Button";
export { ColorVar } from "@library/styles/CssVar";
export { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
export { Tag } from "@library/metas/Tags";
export { default as DateTime } from "@library/content/DateTime";
export { TokenItem } from "@library/metas/TokenItem";
export { Row } from "@library/layout/Row";
export { EditorTabs } from "@library/textEditor/EditorTabs";
export { Gutters } from "@library/layout/components/Gutters";
export { default as NumberedPager } from "@library/features/numberedPager/NumberedPager";
export { default as CheckBox } from "@library/forms/Checkbox";
export { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
export { default as Message } from "@library/messages/Message";
export { PageHeadingBox as HeadingBox } from "@library/layout/PageHeadingBox";
export { PageBox as Box } from "@library/layout/PageBox";

// Our own stuff
export * from "./src/Theme";

export function init(params: { locale?: string }) {
    const { locale = "en" } = params;
    // This is a no-op for now, but we can use this to do some initialization in the future.
    // For example, we could load translations or set up some global state.
    i18n.loadTranslations({});
    i18n.setCurrentLocale(locale);
}
