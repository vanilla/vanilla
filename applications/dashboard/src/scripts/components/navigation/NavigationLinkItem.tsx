/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonType, ButtonTypes } from "@library/forms/buttonTypes";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { DownTriangleIcon, RightTriangleIcon } from "@library/icons/common";
import { ITreeItem } from "@library/tree/types";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { mountPortal } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useRef } from "react";
import { DraggableProvided, DraggableStateSnapshot } from "react-beautiful-dnd";
import useNavigationLinkItemStyles from "./NavigationLinkItem.styles";
import { Row } from "@library/layout/Row";
import { TokenItem } from "@library/metas/TokenItem";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { useRoles } from "@dashboard/roles/roleHooks";
import UserContent from "@library/content/UserContent";

export const DRAGGING_ITEM_PORTAL_ID = "dragging-item-portal";

interface IProps {
    item: ITreeItem<INavigationVariableItem>;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    depth: number;
    onDelete(): void;
    onShow(): void;
    onHide(): void;
    onCollapse(): void;
    onExpand(): void;
    onEdit(): void;
}

export function NavigationLinkItem(props: IProps) {
    const classes = useNavigationLinkItemStyles();

    const selfRef = useRef<HTMLDivElement>();

    const namePlaceholder = t("(untitled)");

    const { depth, item, snapshot, provided, onDelete, onShow, onCollapse, onExpand, onEdit } = props;

    const { data, isExpanded, children } = item;
    const hasChildren = children.length > 0;
    const { name, url, isCustom, isHidden } = data;
    const { isDragging } = snapshot;

    const isChild = depth > 0;
    const isCombining = Boolean(snapshot.combineTargetFor);

    function focusSelf() {
        if (!selfRef.current?.matches(":focus-within")) {
            selfRef.current?.focus();
        }
    }

    function onKeyDown(event: React.KeyboardEvent) {
        switch (event.key) {
            case "Enter":
                event.preventDefault();
                event.stopPropagation();
                props.onEdit();
                break;
            case "Delete":
                event.preventDefault();
                event.stopPropagation();
                props.onDelete();
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
            onDoubleClick={() => props.onEdit()}
            onKeyDown={onKeyDown}
            style={{
                ...provided.draggableProps.style,
            }}
            className={classNames(classes.container, {
                hasChildren,
                isDragging,
                isCombining,
                isExpanded,
                isChild,
                isHiddenItem: isHidden,
            })}
        >
            {hasChildren ? (
                <Button
                    buttonType={ButtonTypes.ICON_COMPACT}
                    className={classes.expandCollapseButton}
                    tabIndex={-1}
                    disabled={isHidden}
                    onClick={() => {
                        (isExpanded ? onCollapse : onExpand)();
                    }}
                >
                    {isExpanded ? <DownTriangleIcon /> : <RightTriangleIcon />}
                </Button>
            ) : (
                <Icon className={classes.linkIcon} icon="editor-link-rich" />
            )}
            <div style={{ flex: 1 }}>
                <Row>
                    <span className={classes.nameColumn}>{displayName}</span>
                    {!hasChildren && <span className={classes.urlColumn}>{displayUrl}</span>}
                </Row>
            </div>
            <span className={classes.spacer} />
            <span className={classes.actions}>
                {item.data.roleIDs && item.data.roleIDs.length > 0 && (
                    <ToolTip label={<RoleToolTipLabel roleIDs={item.data.roleIDs} />}>
                        <ToolTipIcon>
                            <Icon icon="visibility-private" />
                        </ToolTipIcon>
                    </ToolTip>
                )}
                {isHidden && (
                    <Button buttonType={ButtonTypes.ICON_COMPACT} onClick={props.onShow}>
                        <Icon icon="show-content" />
                    </Button>
                )}
                {!isHidden && (
                    <>
                        <Button
                            buttonType={ButtonTypes.ICON_COMPACT}
                            className={classes.editButton}
                            onClick={props.onEdit}
                        >
                            <Icon icon="edit" />
                        </Button>
                        {isCustom ? (
                            <Button
                                buttonType={ButtonType.ICON_COMPACT}
                                className={classes.deleteButton}
                                onClick={props.onDelete}
                            >
                                <Icon icon="delete" />
                            </Button>
                        ) : (
                            <Button
                                buttonType={ButtonType.ICON_COMPACT}
                                className={classes.deleteButton}
                                onClick={props.onHide}
                            >
                                <Icon icon="hide-content" />
                            </Button>
                        )}
                    </>
                )}
            </span>
        </div>
    );

    // Because of positioning issues in modals, we render the dragging item into a portal.
    if (snapshot.isDragging) {
        return mountPortal(content, DRAGGING_ITEM_PORTAL_ID, true) as any;
    }
    return content;
}

function RoleToolTipLabel(props: { roleIDs: number[] }) {
    const roles = useRoles();

    return (
        <div>
            <p>{t("This link is only visible to the following roles")}</p>
            <Row gap={6} style={{ marginTop: 6 }}>
                {props.roleIDs.map((roleID, i) => {
                    return <TokenItem key={i}>{roles.data?.[roleID]?.name}</TokenItem>;
                })}
            </Row>
        </div>
    );
}
