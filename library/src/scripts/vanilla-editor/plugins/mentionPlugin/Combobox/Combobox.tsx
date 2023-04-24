import React, { useCallback, useEffect } from "react";
import Floating from "@library/vanilla-editor/toolbars/Floating";
import {
    comboboxActions,
    comboboxSelectors,
    Data,
    getComboboxStoreById,
    NoData,
    TComboboxItem,
    useActiveComboboxStore,
    useComboboxControls,
    useComboboxSelectors,
} from "@udecode/plate-combobox";
import { useEditorState, useEventEditorSelectors } from "@udecode/plate-core";
import { useVirtualFloating } from "@udecode/plate-floating";
import { toDOMNode, findMentionInput } from "@udecode/plate-headless";
import { t } from "@vanilla/i18n";
import { EMPTY_RECT } from "@vanilla/react-utils";
import { ComboboxProps } from "./Combobox.types";

const ComboboxContent = <TData extends Data = NoData>(
    props: Omit<
        ComboboxProps<TData>,
        "id" | "trigger" | "searchPattern" | "onSelectItem" | "controlled" | "maxSuggestions" | "filter" | "sort"
    >,
) => {
    const { component: Component, items, onRenderItem } = props;

    const targetRange = useComboboxSelectors.targetRange();
    const filteredItems = useComboboxSelectors.filteredItems();
    const highlightedIndex = useComboboxSelectors.highlightedIndex();
    const floatingOptions = useComboboxSelectors.floatingOptions();
    const editor = useEditorState();
    const { getMenuProps, getItemProps } = useComboboxControls();
    const activeComboboxStore = useActiveComboboxStore()!;
    const text = useComboboxSelectors.text() ?? "";
    const storeItems = useComboboxSelectors.items();
    const filter = activeComboboxStore.use.filter?.();
    const sort = activeComboboxStore.use.sort?.();
    const maxSuggestions = activeComboboxStore.use.maxSuggestions?.() ?? storeItems.length;

    // Update items
    useEffect(() => {
        items && comboboxActions.items(items);
    }, [items]);

    // Filter items
    useEffect(() => {
        comboboxActions.filteredItems(
            storeItems
                .filter(filter ? filter(text) : (value) => value.text.toLowerCase().startsWith(text.toLowerCase()))
                .sort(sort?.(text))
                .slice(0, maxSuggestions),
        );
    }, [filter, sort, storeItems, maxSuggestions, text]);

    // Get target range rect
    const getBoundingClientRect = useCallback(() => {
        if (!targetRange) {
            return EMPTY_RECT;
        }

        const mentionInput = findMentionInput(editor);

        if (!mentionInput) {
            return EMPTY_RECT;
        }

        const domNode = toDOMNode(editor, mentionInput[0]);
        if (!domNode) {
            return EMPTY_RECT;
        }
        return domNode.getBoundingClientRect();
    }, [editor, targetRange]);

    // Update popper position
    const floatingResult = useVirtualFloating({
        ...floatingOptions,
        placement: "bottom-start",
        getBoundingClientRect,
    });

    const menuProps = getMenuProps ? getMenuProps({}, { suppressRefError: true }) : { ref: null };

    const { listWrapperClassName, listClassName, listItemClassName, highlightedListItemClassName } = props;

    return (
        <Floating {...floatingResult}>
            <span className={listWrapperClassName}>
                <ul {...menuProps} aria-label={t("@mention user suggestions")} role="listbox" className={listClassName}>
                    {Component ? Component({ store: activeComboboxStore }) : null}

                    {filteredItems.map((item, index) => {
                        const Item = onRenderItem
                            ? onRenderItem({ search: text, item: item as TComboboxItem<TData> })
                            : item.text;

                        const highlighted = index === highlightedIndex;

                        return (
                            <li
                                key={item.key}
                                className={highlighted ? highlightedListItemClassName : listItemClassName}
                                {...getItemProps({
                                    item,
                                    index,
                                })}
                                aria-selected={highlighted}
                                role="option"
                                onMouseDown={(e) => {
                                    e.preventDefault();
                                    const onSelectItem = getComboboxStoreById(
                                        comboboxSelectors.activeId(),
                                    )?.get.onSelectItem();
                                    onSelectItem?.(editor, item);
                                }}
                                onMouseEnter={(e) => {
                                    comboboxActions.highlightedIndex(index);
                                }}
                            >
                                {Item}
                            </li>
                        );
                    })}
                </ul>
            </span>
        </Floating>
    );
};

/**
 * Register the combobox id, trigger, onSelectItem
 * Renders the combobox if active.
 */
export const Combobox = <TData extends Data = NoData>({
    id,
    trigger,
    searchPattern,
    onSelectItem,
    controlled,
    maxSuggestions,
    filter,
    sort,
    floatingOptions,
    disabled: _disabled,
    ...props
}: ComboboxProps<TData>) => {
    const storeItems = useComboboxSelectors.items();
    const disabled = _disabled ?? (!storeItems.length && !props.items?.length);

    const editor = useEditorState();
    const focusedEditorId = useEventEditorSelectors.focus?.();
    const combobox = useComboboxControls();
    const activeId = useComboboxSelectors.activeId();

    useEffect(() => {
        if (floatingOptions) {
            comboboxActions.floatingOptions(floatingOptions);
        }
    }, [floatingOptions]);

    useEffect(() => {
        comboboxActions.setComboboxById({
            id,
            trigger,
            searchPattern,
            controlled,
            onSelectItem,
            maxSuggestions,
            filter,
            sort,
        });
    }, [id, trigger, searchPattern, controlled, onSelectItem, maxSuggestions, filter, sort]);

    if (!combobox || !editor.selection || focusedEditorId !== editor.id || activeId !== id || disabled) {
        return null;
    }

    return <ComboboxContent {...props} />;
};
