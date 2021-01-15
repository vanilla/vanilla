/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import TreeItem from "@library/tree/TreeItem";
import {
    IDragState,
    IFlattenedItem,
    IRenderItemParams,
    ItemID,
    ITreeData,
    ITreeDestinationPosition,
    ITreeItem,
    ITreeSourcePosition,
    Path,
} from "@library/tree/types";
import { useDelayedFunction } from "@vanilla/react-utils";
import {
    calculateFinalDropPositions,
    flattenTree,
    getDestinationPath,
    getIndexById,
    getItemById,
    getParent,
} from "@library/tree/utils";
import React, { ReactNode, useCallback, useMemo, useRef, useState } from "react";
import {
    DragDropContext,
    Draggable,
    DraggableProvided,
    DraggableStateSnapshot,
    DragStart,
    DragUpdate,
    Droppable,
    DroppableProvided,
    DropResult,
} from "react-beautiful-dnd";
import { getBox } from "css-box-model";

interface Props<D> {
    tree: ITreeData<D>;
    onExpand: (itemID: ItemID, path: Path) => void;
    onCollapse: (itemID: ItemID, path: Path) => void;
    onDragStart?: (itemID: ItemID) => void;
    onDragUpdate?: (update: DragUpdate) => void;
    onDragEnd: (sourcePosition: ITreeSourcePosition, destinationPosition?: ITreeDestinationPosition) => void;
    offsetPerLevel: number;
    isDragEnabled: boolean;
    isNestingEnabled: boolean;
    renderItem(params: IRenderItemParams<D>);
}

export default function Tree<D = {}>(props: Props<D>) {
    const { tree, onExpand, onCollapse, isNestingEnabled, isDragEnabled, offsetPerLevel } = props;

    const expandTimer = useDelayedFunction(500);
    const containerRef = useRef<HTMLElement>();
    const itemRef = useRef<Record<ItemID, HTMLElement | null>>({});
    const dragState = useRef<IDragState>();
    const [draggedItemID, setDraggedItemID] = useState<ItemID>();

    const flattenedTree = useMemo(() => flattenTree(tree), [tree]);

    const renderItem = useCallback(
        function (flatItem: IFlattenedItem<D>, index: number): ReactNode {
            const calculateEffectivePath = (flatItem: IFlattenedItem<D>, snapshot: DraggableStateSnapshot): Path => {
                if (
                    dragState.current &&
                    draggedItemID === flatItem.item.id &&
                    (dragState.current.destination || dragState.current.combine)
                ) {
                    const { source, destination, combine, horizontalLevel, mode } = dragState.current;
                    // We only update the path when it's dragged by keyboard or drop is animated
                    if (mode === "SNAP" || snapshot.isDropAnimating) {
                        if (destination) {
                            // Between two items
                            return getDestinationPath(flattenedTree, source.index, destination.index, horizontalLevel);
                        }
                        if (combine) {
                            // Hover on other item while dragging
                            return getDestinationPath(
                                flattenedTree,
                                source.index,
                                getIndexById(flattenedTree, combine.draggableId),
                                horizontalLevel,
                            );
                        }
                    }
                }
                return flatItem.path;
            };

            const renderDraggableItem = (flatItem: IFlattenedItem<D>) =>
                function DraggableItem(provided: DraggableProvided, snapshot: DraggableStateSnapshot) {
                    const currentPath: Path = calculateEffectivePath(flatItem, snapshot);
                    if (snapshot.isDropAnimating) {
                        expandTimer.stop();
                    }
                    return (
                        <TreeItem
                            item={flatItem.item}
                            path={currentPath}
                            offsetPerLevel={offsetPerLevel}
                            onExpand={onExpand}
                            onCollapse={onCollapse}
                            renderItem={props.renderItem}
                            provided={provided}
                            snapshot={snapshot}
                            itemRef={(itemId, element) => {
                                itemRef.current[itemId] = element;
                            }}
                        />
                    );
                };

            return (
                <Draggable
                    key={flatItem.item.id}
                    draggableId={flatItem.item.id.toString()}
                    index={index}
                    isDragDisabled={!isDragEnabled}
                >
                    {renderDraggableItem(flatItem)}
                </Draggable>
            );
        },
        [
            expandTimer,
            draggedItemID,
            flattenedTree,
            isDragEnabled,
            offsetPerLevel,
            onCollapse,
            onExpand,
            props.renderItem,
        ],
    );

    function isExpandable(fi: IFlattenedItem<D>): boolean {
        return Boolean(fi.item.hasChildren) && !fi.item.isExpanded;
    }

    function getDroppedLevel(): number | undefined {
        // If we are not currently dragging or rendered, abort.
        if (!dragState.current || !containerRef.current) {
            return undefined;
        }

        // Get the left position of the container and the currently dragged element.
        const containerLeft = getBox(containerRef.current).contentBox.left;
        const itemElement = itemRef.current[draggedItemID!];

        if (itemElement) {
            // Get the left position of the dragged element.
            const currentLeft: number = getBox(itemElement).contentBox.left;
            // Calculate it's position relative to the container.
            const relativeLeft: number = Math.max(currentLeft - containerLeft, 0);
            // The level we are dropping this item is calculated using the left position relative to parent
            // divided by the space between levels.
            return Math.floor((relativeLeft + offsetPerLevel / 2) / offsetPerLevel) + 1;
        }

        return undefined;
    }

    function patchDroppableProvided(provided: DroppableProvided): DroppableProvided {
        return {
            ...provided,
            innerRef: (el: HTMLElement) => {
                containerRef.current = el;
                provided.innerRef(el);
            },
        };
    }

    function onPointerMove() {
        if (dragState.current) {
            dragState.current = {
                ...dragState.current!,
                horizontalLevel: getDroppedLevel(),
            };
        }
    }

    function onDragStart(result: DragStart) {
        dragState.current = {
            source: result.source,
            destination: result.source,
            mode: result.mode,
        };
        setDraggedItemID(result.draggableId);
        if (props.onDragStart) props.onDragStart(result.draggableId);
    }

    function onDragUpdate(update: DragUpdate) {
        if (props.onDragUpdate) {
            props.onDragUpdate(update);
        }
        if (!dragState) {
            return;
        }

        expandTimer.stop();
        if (update.combine) {
            const { draggableId } = update.combine;
            const draggable = getItemById(flattenedTree, draggableId);
            if (draggable && isExpandable(draggable)) {
                expandTimer.start(() => onExpand(draggableId, draggable.path));
            }
        }
        dragState.current = {
            ...dragState.current!,
            destination: update.destination,
            combine: update.combine,
        };
    }

    function onDragEnd(result: DropResult) {
        expandTimer.stop();

        const finalDragState: IDragState = {
            ...dragState.current!,
            source: result.source,
            destination: result.destination,
            combine: result.combine,
        };

        setDraggedItemID(undefined);

        const { sourcePosition, destinationPosition } = calculateFinalDropPositions(
            tree,
            flattenedTree,
            finalDragState,
        );

        if (props.onDragEnd) props.onDragEnd(sourcePosition, destinationPosition);

        dragState.current = undefined;
    }

    const renderedItems = useMemo(() => flattenedTree.map((fi, index) => renderItem(fi, index)), [
        renderItem,
        flattenedTree,
    ]);

    /**
     * Keyboard handler for arrow up, arrow down, home, end and escape.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNavNode.tsx
     * @param event
     */
    function onKeyDown(e: React.KeyboardEvent) {
        const container = containerRef.current;
        if (!isDragEnabled || dragState.current || !container) {
            return;
        }
        // Get draggable items.
        const items = Array.from(container!.querySelectorAll<HTMLElement>("*[data-rbd-draggable-id]"));
        let focused = container!.querySelector<HTMLElement>("*[data-rbd-draggable-id]:focus-within");

        const focusItemById = (id: ItemID) =>
            container.querySelector<HTMLElement>(`*[data-rbd-draggable-id=${id}]`)?.focus();

        // If no items are focused when pressing a key, focus the first one.
        if (!focused) {
            focused = items[0];
            focused!.focus();
        }

        // Get the id and index of the focused element.
        const focusedId = focused.dataset.rbdDraggableId;
        const index = items.indexOf(focused);

        // Find the focused item and it's parent in the tree.
        let focusedItem: ITreeItem<D> | undefined;
        let focusedPath: Path | undefined;
        let parentItem: ITreeItem<D> | undefined;
        if (focusedId !== undefined) {
            const flattenedFocusedItem = getItemById(flattenedTree, focusedId);
            if (flattenedFocusedItem) {
                focusedItem = flattenedFocusedItem.item;
                focusedPath = flattenedFocusedItem.path;
                parentItem = getParent(tree, focusedPath);
            }
        }

        // Get the siblings of the focused item.
        const prev = index > 0 ? items[index - 1] : undefined;
        const next = index + 1 < items.length ? items[index + 1] : undefined;
        const first = items[0];
        const last = items[items.length - 1];
        const isFirst = focused === first;
        const isLast = focused === last;

        // Handle keys.
        switch (e.key) {
            case "Escape":
                e.preventDefault();
                e.stopPropagation();
                break;
            case "ArrowDown":
                if (!isLast && next) {
                    e.preventDefault();
                    e.stopPropagation();
                    next.focus();
                }
                break;
            case "ArrowUp":
                if (!isFirst && prev) {
                    e.preventDefault();
                    e.stopPropagation();
                    prev.focus();
                }
                break;
            case "ArrowRight":
                /**
                 * Only applies to items with children.
                 * When focus is on a closed node, opens the node; focus does not move.
                 * When focus is on a open node, moves focus to the first child node.
                 * When focus is on an end node, does nothing.
                 */
                if (focusedItem && focusedItem.children.length > 0) {
                    if (!focusedItem.isExpanded) {
                        e.stopPropagation();
                        onExpand(focusedItem.id, focusedPath!);
                    } else {
                        const firstChildId = focusedItem.children.length > 0 ? focusedItem.children[0] : null;
                        if (firstChildId) {
                            focusItemById(firstChildId);
                        }
                    }
                }
                break;
            case "ArrowLeft":
                /*
                    When focus is on an open node, closes the node.
                    When focus is on a child node that is also either an end node or a closed node, moves focus to its parent node.
                    When focus is on a root node that is also either an end node or a closed node, does nothing.
                */
                if (focused && focusedItem) {
                    if (focusedItem.children.length > 0 && focusedItem.isExpanded) {
                        e.stopPropagation();
                        onCollapse(focusedItem.id, focusedPath!);
                    } else if (parentItem) {
                        focusItemById(parentItem.id);
                    }
                }
                break;
            case "Home":
                if (!isFirst && first) {
                    e.preventDefault();
                    e.stopPropagation();
                    first.focus();
                }
                break;
            case "End":
                if (!isLast && last) {
                    e.preventDefault();
                    e.stopPropagation();
                    last.focus();
                }
                break;
        }
    }

    return (
        <DragDropContext onDragStart={onDragStart} onDragEnd={onDragEnd} onDragUpdate={onDragUpdate}>
            <Droppable droppableId="tree" isCombineEnabled={isNestingEnabled} ignoreContainerClipping>
                {(provided) => {
                    const finalProvided = patchDroppableProvided(provided);
                    return (
                        <div
                            ref={finalProvided.innerRef}
                            style={{ pointerEvents: "auto" }}
                            onTouchMove={onPointerMove}
                            onMouseMove={onPointerMove}
                            onKeyDown={onKeyDown}
                            {...finalProvided.droppableProps}
                        >
                            {renderedItems}
                            {finalProvided.placeholder}
                        </div>
                    );
                }}
            </Droppable>
        </DragDropContext>
    );
}
