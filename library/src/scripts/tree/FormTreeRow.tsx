/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { formTreeClasses } from "@library/tree/FormTree.classes";
import { useFormTreeContext } from "@library/tree/FormTreeContext";
import { FormTreeRowEditModal } from "@library/tree/FormTreeRowEditModal";
import { FormTreeRowFormControl } from "@library/tree/FormTreeRowFormControl";
import { IRenderItemParams } from "@library/tree/types";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useFocusWatcher, useLastValue } from "@vanilla/react-utils";
import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from "react";

interface IProps<ItemDataType> extends IRenderItemParams<ItemDataType> {
    disabled?: boolean;
    isFirstItem?: boolean;
    RowContentsComponent?: React.ComponentType<ItemDataType>;
}

/**
 * as React.ComponentType<ItemDataType> Component representing a single row of a form tree.
 *
 * - Has a compact made triggered through the context.
 *   - Text buttons become icons.
 *   - Edit triggers a modal instead of inline editing.
 * - Rows can be deletable or hideable depending on their data.
 */
export function FormTreeRow<ItemDataType>(props: IProps<ItemDataType>) {
    const { item } = props;

    //
    // Component state.
    //
    const treeContext = useFormTreeContext<ItemDataType>();
    const { isCompact, isItemEditable, isItemDeletable, isItemHideable, markItemHidden, isItemHidden } = treeContext;
    const isEditable = isItemEditable?.(item.data) ?? true;
    const isHideable = isItemHideable?.(item.data) ?? false;
    const isDeletable = isItemDeletable?.(item.data) ?? !isHideable;
    const isHidden = isItemHidden?.(item.data) ?? false;

    const anyActionsAvailable = isHideable || isEditable || isDeletable;

    // Hidden items get marked as disabled.
    const disabled = props.disabled || isHidden;

    const [internalValue, setInternalValue] = useState<ItemDataType>(props.item.data);
    const isEditing = item.id === treeContext.currentEditID;
    const [isHovered, setIsHovered] = useState(false);

    //
    // Utility functions
    //
    const stopEditing = useCallback(
        (shouldResetValue: boolean = false) => {
            if (shouldResetValue) {
                setInternalValue(item.data);
            }
            treeContext.setCurrentEditID(null);
        },
        [setInternalValue, treeContext.setCurrentEditID],
    );

    function save() {
        treeContext.saveItem(item.id, internalValue);
        stopEditing();
    }

    // We can't just "focus" the inputs when we start editing.
    // This is because the inputs are disabled at that point.
    // Instead when editing starts we can save a ref of the element we want to focus.
    const focusAfterEditStart = useRef<HTMLElement | null>(null);
    useLayoutEffect(() => {
        if (focusAfterEditStart.current instanceof HTMLElement) {
            focusAfterEditStart.current.focus();
            focusAfterEditStart.current = null;
        }
    });

    function startEditing(focusFrom?: EventTarget) {
        treeContext.setCurrentEditID(item.id);

        // Try to focus the an input.
        const selector = "input, textarea, select";

        if (focusFrom instanceof Element) {
            // Try to focus the closest input parent to this element.
            if (focusFrom instanceof HTMLElement && focusFrom.matches(selector)) {
                focusAfterEditStart.current = focusFrom;
                return;
            }

            const closestAround = focusFrom.closest(selector) ?? focusFrom.querySelector(selector);
            if (closestAround instanceof HTMLElement) {
                focusAfterEditStart.current = closestAround;
                return;
            }
        }

        // No dice focusing so far.
        // Lets see if we can just grab the first element
        const firstInput = selfRef.current?.querySelector(selector);
        if (firstInput instanceof HTMLElement) {
            focusAfterEditStart.current = firstInput;
        }
    }

    function focusSelf() {
        selfRef.current?.focus();
    }

    function onFocus() {
        treeContext.setSelectedItemID(item.id);
    }

    //
    // Selection management
    //

    const selfRef = useRef<HTMLDivElement | null>(null);
    // Try to save when we lose focus.
    useFocusWatcher(selfRef, (hasFocus) => {
        if (isEditing && !hasFocus) {
            save();
        }
    });

    const isSelected = item.id === treeContext.selectedItemID;
    const lastIsSelected = useLastValue(isSelected);
    useEffect(() => {
        if (lastIsSelected && !isSelected && isEditing) {
            // If we lose our selection make sure we remove our editing state.
            save();
        }
    }, [isSelected, lastIsSelected, stopEditing]);

    // We implement a roving tab index pattern.
    // https://www.w3.org/TR/wai-aria-practices-1.1/#kbd_roving_tabindex
    // This means only 1 item is focusable at a time.
    // However, by default there is no selected item, and in that case
    // The first item will be the selectable one.
    const isFocusable = isSelected || (treeContext.selectedItemID === null && props.isFirstItem);

    // Simple keyboard event handler for edit, save, and stop.
    function onKeyDown(event: React.KeyboardEvent) {
        switch (event.key) {
            case "Enter":
                event.preventDefault();
                event.stopPropagation();
                if (isEditing) {
                    save();
                } else {
                    isEditable && startEditing();
                }
                break;
            case "Escape":
                event.preventDefault();
                event.stopPropagation();
                stopEditing(true);
                break;
        }
    }

    const classes = formTreeClasses();
    const iconType = treeContext.getRowIcon ? treeContext.getRowIcon(item) : "data-folder-tabs";

    const cancelAction = isEditable && (
        <Button
            onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                stopEditing(true);
            }}
            buttonType={ButtonTypes.TEXT}
        >
            <span>{t("Cancel")}</span>
        </Button>
    );

    const applyAction = isEditable && (
        <Button
            onClick={(e) => {
                e.preventDefault();
                // Make sure propagation doesn't go to the row.
                e.stopPropagation();
                save();
            }}
            buttonType={ButtonTypes.TEXT_PRIMARY}
        >
            <span>{t("Apply")}</span>
        </Button>
    );

    const editAction = isEditable && !isHidden && (
        <Button
            key={"edit"}
            onClick={(e) => {
                e.preventDefault();
                // Make sure propagation doesn't go to the row.
                e.stopPropagation();
                isEditable && startEditing();
            }}
            buttonType={isCompact ? ButtonTypes.ICON : ButtonTypes.TEXT_PRIMARY}
        >
            {/*
                We display an icon on mobile in the row.
            */}
            <span className={isCompact ? visibility().visuallyHidden : ""}>{t("Edit")}</span>
            {isCompact && <Icon icon="data-pencil" />}
        </Button>
    );

    const deleteAction = isDeletable && (
        <Button
            key={"delete"}
            onClick={(e) => {
                e.preventDefault();
                // Make sure propagation doesn't go to the row.
                e.stopPropagation();
                treeContext.deleteItem(item.id);
            }}
            buttonType={isCompact ? ButtonTypes.ICON : ButtonTypes.TEXT}
            className={isCompact ? "" : classes.deleteHideAction}
        >
            {/*
                We display an icon on mobile in the row.
            */}
            <span className={isCompact ? visibility().visuallyHidden : ""}>{t("Delete")}</span>
            {isCompact && <Icon icon={"data-trash"} />}
        </Button>
    );

    const hideAction = markItemHidden != null && isHideable && (
        <Button
            key={"hide"}
            onClick={(e) => {
                e.preventDefault();
                // Make sure propagation doesn't go to the row.
                e.stopPropagation();
                markItemHidden(item.id, item.data, !isHidden);
            }}
            buttonType={isCompact ? ButtonTypes.ICON : ButtonTypes.TEXT}
            className={isCompact || isHidden ? "" : classes.deleteHideAction}
        >
            {/*
                We display an icon on mobile in the row.
            */}
            <span className={isCompact ? visibility().visuallyHidden : ""}>{isHidden ? t("Show") : t("Hide")}</span>
            {isCompact && <Icon icon={isHidden ? "editor-eye-slash" : "editor-eye"} />}
        </Button>
    );

    return (
        <div
            // React DnD library.
            ref={(ref) => {
                // Get a ref for ourselves.
                selfRef.current = ref;
                // Make sure ReactDnD get it's ref.
                props.provided.innerRef(ref);
            }}
            {...props.provided.draggableProps}
            {...props.provided.dragHandleProps}
            // Styles
            style={props.provided.draggableProps.style}
            className={cx(classes.row, {
                [classes.rowActive]: isSelected || isHovered || isEditing,
                [classes.rowDragged]: props.snapshot.isDragging,
                [classes.rowCompact]: isCompact,
                isItemHidden: isHidden,
            })}
            // Event handlers and Accessibility
            onMouseEnter={() => {
                setIsHovered(true);
            }}
            onMouseLeave={() => {
                setIsHovered(false);
            }}
            onClick={() => {
                if (!isEditing) {
                    focusSelf();
                }
            }}
            onDoubleClick={(e) => {
                e.preventDefault();
                isEditable && startEditing(e.target);
            }}
            onKeyDown={onKeyDown}
            onFocus={onFocus}
            tabIndex={isFocusable ? 0 : -1}
            role="treeitem"
        >
            {iconType && (
                <div className={classes.rowIconWrapper}>
                    <Icon className={classes.rowIcon} icon={iconType} />
                </div>
            )}
            {props.RowContentsComponent ? (
                <props.RowContentsComponent {...props.item.data!} />
            ) : (
                // React.createElement(props.RowContentsComponent as React.ComponentType<ItemDataType>, props.item.data)
                <JsonSchemaForm
                    // Inputs are disabled when we aren't editing (to take them out of the tab order)
                    // However, in compact mode the actual editable inputs are in a modal
                    // So they are always hidden.
                    disabled={!isEditing || isCompact}
                    schema={treeContext.itemSchema}
                    instance={internalValue}
                    onChange={setInternalValue}
                    FormControl={FormTreeRowFormControl}
                />
            )}
            {anyActionsAvailable && (
                <>
                    {isCompact ? (
                        <div className={cx(classes.actionWrapper, classes.actionWrapperCompact)}>
                            {(isSelected || isHovered) && (
                                <>
                                    {editAction}
                                    {deleteAction}
                                    {hideAction}
                                </>
                            )}
                        </div>
                    ) : (
                        <div className={classes.actionWrapper}>
                            {/*
                    Row actions on desktop
                */}
                            {isEditing && (
                                <>
                                    {cancelAction}
                                    {applyAction}
                                </>
                            )}
                            {!isEditing && (isSelected || isHovered) && (
                                <>
                                    {editAction}
                                    {hideAction}
                                    {deleteAction}
                                </>
                            )}
                        </div>
                    )}
                    {isCompact && (
                        <FormTreeRowEditModal
                            isVisible={isEditing}
                            onClose={() => {
                                stopEditing(true);
                            }}
                            footerActions={
                                <>
                                    {cancelAction}
                                    {applyAction}
                                </>
                            }
                            form={
                                <JsonSchemaForm
                                    schema={treeContext.itemSchema}
                                    instance={internalValue}
                                    onChange={setInternalValue}
                                    vanillaUI
                                />
                            }
                        />
                    )}
                </>
            )}
        </div>
    );
}
