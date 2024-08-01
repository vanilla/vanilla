/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { Ref, useEffect, useImperativeHandle, useRef, useState } from "react";
import { DraggableProvided, DraggableStateSnapshot } from "react-beautiful-dnd";
import { ITreeItem } from "@library/tree/types";
import Button from "@library/forms/Button";
import useNavigationLinkItemStyles from "./NavigationLinkItem.styles";
import { DownTriangleIcon, RightTriangleIcon } from "@library/icons/common";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import { useLastValue } from "@vanilla/react-utils";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { mountPortal } from "@vanilla/react-utils";

export const DRAGGING_ITEM_PORTAL_ID = "dragging-item-portal";

function formatUrl(str: string) {
    str = str.trim();
    const hasSchema = /^https?:\/\//i.test(str);
    const isRelative = /^~?\//.test(str);
    // Prevent javascript execution.
    if (/javascript:/i.test(str)) {
        return "/";
    }
    // If the url is valid already, return it.
    const isValid = hasSchema || isRelative;
    if (isValid) {
        return str;
    }
    // If the first part of the url (before the first /) is a hostname (ex: something.com) add http://
    const hasHostname = /^[a-z0-9]+[a-z0-9-]*(\.[a-z0-9]+[a-z0-9-]*)+(\/.*)?$/i.test(str);
    if (hasHostname) {
        return "http://" + str;
    }
    // If the url starts with a tilde add a /
    const hasTilde = str.startsWith("~");
    if (hasTilde) {
        return "~/" + str.slice(1);
    }
    // Add a leading /
    return "/" + str;
}

interface IProps {
    item: ITreeItem<INavigationVariableItem>;
    disabled: boolean;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    depth: number;
    onDelete(): void;
    onShow(): void;
    onSave(data: INavigationVariableItem): void;
    onCollapse(): void;
    onExpand(): void;
    onStartEditing(): void;
    onStopEditing(): void;
}

export interface INavigationLinkItemHandle {
    edit(): void;
}

export const NavigationLinkItem = React.forwardRef(function NavigationLinkItemImpl(
    props: IProps,
    ref: Ref<INavigationLinkItemHandle>,
) {
    const classes = useNavigationLinkItemStyles();

    const selfRef = useRef<HTMLDivElement>();
    const nameInputRef = useRef<HTMLInputElement>(null);
    const toInputRef = useRef<HTMLInputElement>(null);

    const namePlaceholder = t("(untitled)");
    const urlPlaceholder = t("Paste or type url");

    const {
        depth,
        item,
        disabled,
        snapshot,
        provided,
        onDelete,
        onShow,
        onSave,
        onCollapse,
        onExpand,
        onStartEditing,
        onStopEditing,
    } = props;

    const { data, isExpanded, hasChildren } = item;
    const { name, url, isCustom, isHidden } = data;
    const { isDragging } = snapshot;

    const isChild = depth > 0;
    const isCombining = Boolean(snapshot.combineTargetFor);

    const [isEditing, setIsEditing] = useState(false);
    const wasEditing = useLastValue(isEditing);
    const [editingData, setEditingData] = useState<INavigationVariableItem>(data);

    function focusSelf() {
        if (!selfRef.current?.matches(":focus-within")) {
            selfRef.current?.focus();
        }
    }

    function stopEditing() {
        setIsEditing(false);
        onStopEditing();
    }

    function startEditing() {
        if (!isDragging && !isHidden) {
            setIsEditing(true);
            onStartEditing();
        }
    }

    function save() {
        stopEditing();
        onSave({
            ...editingData,
            name: editingData.name.trim(),
            url: formatUrl(editingData.url),
        });
    }

    useImperativeHandle(ref, () => ({
        edit: startEditing,
    }));

    useEffect(() => {
        // Reset editing data when we are switching modes or when we receive new data.
        setEditingData(data);
    }, [data, isEditing]);

    useEffect(() => {
        // Focus the first input when editing.
        if (isEditing && !wasEditing) {
            nameInputRef.current?.focus();
        } else if (!isEditing && wasEditing) {
            selfRef.current?.focus(); // Preserve focus.
        }
    }, [isEditing, wasEditing]);

    function onNameChange(event: React.ChangeEvent<HTMLInputElement>) {
        const value = event.target.value;
        setEditingData((data: INavigationVariableItem) => ({
            ...data,
            name: value,
        }));
    }

    function onUrlChange(event: React.ChangeEvent<HTMLInputElement>) {
        const value = event.target.value;
        setEditingData((data: INavigationVariableItem) => ({
            ...data,
            url: value,
        }));
    }

    function onStopEditingClick(event: React.MouseEvent) {
        event.preventDefault();
        stopEditing();
    }

    function onSaveClick(event: React.MouseEvent) {
        event.preventDefault();
        save();
    }

    function onStartEditingClick(event: React.MouseEvent) {
        event.preventDefault();
        startEditing();
    }

    function onShowClick(event: React.MouseEvent) {
        event.preventDefault();
        onShow();
    }

    function onDeleteClick(event: React.MouseEvent) {
        event.preventDefault();
        onDelete();
    }

    function onKeyDown(event: React.KeyboardEvent) {
        switch (event.key) {
            case "Enter":
                if (isEditing) {
                    save();
                } else {
                    startEditing();
                }
                break;
            case "Escape":
                event.preventDefault();
                event.stopPropagation();
                stopEditing();
                break;
            case "Delete":
                if (!isEditing) {
                    onDelete();
                }
                break;
        }
    }

    const displayName = name.length ? name : namePlaceholder;
    const displayUrl = url.length ? url : "/";

    const content = (
        <div
            ref={(ref) => {
                selfRef.current = ref!;
                provided.innerRef(ref);
            }}
            {...provided.draggableProps}
            {...provided.dragHandleProps}
            onClick={focusSelf}
            onDoubleClick={startEditing}
            onKeyDown={onKeyDown}
            style={{
                ...provided.draggableProps.style,
                pointerEvents: disabled ? "none" : "all",
            }}
            className={classNames(classes.container, {
                hasChildren,
                isEditing,
                isDragging,
                isCombining,
                isExpanded,
                isChild,
                isHiddenItem: isHidden,
            })}
        >
            <Button
                buttonType={ButtonTypes.CUSTOM}
                className={classes.expandCollapseButton}
                tabIndex={-1}
                style={{ visibility: hasChildren ? "visible" : "hidden" }}
                disabled={isHidden}
                onClick={() => {
                    (isExpanded ? onCollapse : onExpand)();
                }}
            >
                {isExpanded && hasChildren ? <DownTriangleIcon /> : <RightTriangleIcon />}
            </Button>
            <span className={classes.nameColumn}>
                {isEditing ? (
                    <input
                        ref={nameInputRef}
                        className={classes.editableInput}
                        maxLength={255}
                        type="text"
                        value={editingData!.name}
                        placeholder={namePlaceholder}
                        onChange={onNameChange}
                    />
                ) : (
                    displayName
                )}
            </span>
            {!hasChildren && (
                <span className={classes.urlColumn}>
                    {isEditing ? (
                        <input
                            ref={toInputRef}
                            className={classes.editableInput}
                            maxLength={255}
                            disabled={!isCustom}
                            type="text"
                            value={editingData!.url}
                            placeholder={urlPlaceholder}
                            onChange={onUrlChange}
                        />
                    ) : (
                        displayUrl
                    )}
                </span>
            )}
            <span className={classes.spacer} />
            <span className={classes.actions}>
                {isEditing && (
                    <>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            className={classes.cancelButton}
                            onClick={onStopEditingClick}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button buttonType={ButtonTypes.TEXT} className={classes.applyButton} onClick={onSaveClick}>
                            {t("Apply")}
                        </Button>
                    </>
                )}
                {!isEditing && isHidden && (
                    <Button buttonType={ButtonTypes.TEXT} className={classes.showButton} onClick={onShowClick}>
                        {t("Show")}
                    </Button>
                )}
                {!isEditing && !isHidden && (
                    <Button buttonType={ButtonTypes.TEXT} className={classes.editButton} onClick={onStartEditingClick}>
                        {t("Edit")}
                    </Button>
                )}
                {!isEditing && !isHidden && (
                    <Button buttonType={ButtonTypes.TEXT} className={classes.deleteButton} onClick={onDeleteClick}>
                        {isCustom ? t("Delete") : t("Hide")}
                    </Button>
                )}
            </span>
        </div>
    );

    // Because of positioning issues in modals, we render the dragging item into a portal.
    if (snapshot.isDragging) {
        return mountPortal(content, DRAGGING_ITEM_PORTAL_ID, true) as any;
    }
    return content;
});
