/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    getIndexAmongSiblings,
    getParentPath,
    getPathOnLevel,
    hasSameParent,
    isTopOfSubtree,
    moveAfterPath,
} from "@library/tree/path";
import {
    FlattenedTree,
    IDragState,
    IFlattenedItem,
    ItemID,
    ITreeData,
    ITreeDestinationPosition,
    ITreeItem,
    ITreeItemMutation,
    ITreeSourcePosition,
    Path,
} from "@library/tree/types";
import { castDraft, notEmpty, uuidv4 } from "@vanilla/utils";
import produce from "immer";

const between = (min: number, max: number, number: number) => Math.min(max, Math.max(min, number));

/**
 * Transforms tree structure into flat list of items for rendering purposes.
 * We recursively go through all the elements and its children first on each level
 */
export function flattenTree<D>(tree: ITreeData<D>, path: Path = []): FlattenedTree<D> {
    if (!tree.items[tree.rootId]) return [];
    return tree.items[tree.rootId].children.reduce<FlattenedTree<D>>((accum, itemId, index) => {
        // iterating through all the children on the given level
        const item = tree.items[itemId];
        const currentPath = [...path, index];
        // we create a flattened item for the current item
        const currentItem = createFlattenedItem(item, currentPath);
        // we flatten its children
        const children = flattenChildren(tree, item, currentPath);
        // append to the accumulator
        return [...accum, currentItem, ...children];
    }, []);
}

/**
 * Flatten the children of the given subtree
 */
const flattenChildren = <D>(tree: ITreeData<D>, item: ITreeItem<D>, currentPath: Path) =>
    item.isExpanded ? flattenTree({ rootId: item.id, items: tree.items }, currentPath) : [];

/**
 * Constructs a new FlattenedItem
 */
function createFlattenedItem<D>(item: ITreeItem<D>, currentPath: Path): IFlattenedItem<D> {
    return {
        item,
        path: currentPath,
    };
}

/**
 * Find an item in the tree using it's path
 */
export function getItem<D>(tree: ITreeData<D>, path: Path): ITreeItem<D> {
    let cursor: ITreeItem<D> = tree.items[tree.rootId];

    for (const i of path) {
        cursor = tree.items[cursor.children[i]];
    }

    return cursor;
}

/**
 * Get an item's parent in the tree using the item's path
 */
export function getParent<D>(tree: ITreeData<D>, path: Path): ITreeItem<D> {
    const parentPath: Path = getParentPath(path);
    return getItem(tree, parentPath);
}

/**
 * Gets the index of an item among it's siblings using it's path
 */
export function getTreePosition<D>(tree: ITreeData<D>, path: Path): ITreeSourcePosition {
    const parent: ITreeItem<D> = getParent(tree, path);
    const index: number = getIndexAmongSiblings(path);
    return {
        parentId: parent.id,
        index,
    };
}

/**
 * Calculates the source path after drag&drop ends
 */
export const getSourcePath = <D>(flattenedTree: FlattenedTree<D>, sourceIndex: number): Path =>
    flattenedTree[sourceIndex].path;

function calculateFinalLevel(sourcePath: Path, upperPath: Path, lowerPath?: Path, level?: number): number {
    const upperLevel: number = upperPath.length;
    const lowerLevel: number = lowerPath ? lowerPath.length : 1;
    const sourceLevel: number = sourcePath.length;
    if (typeof level === "number") {
        // Explicit disambiguation based on level
        // Final level has to be between the levels of bounding items, inclusive
        return between(lowerLevel, upperLevel, level);
    }
    // Automatic disambiguation based on the initial level
    return sourceLevel <= lowerLevel ? lowerLevel : upperLevel;
}

/**
 * Calculates the destination path after drag&drop ends
 * During dragging the items are displaced based on the location of the dragged item.
 * Displacement depends on which direction the item is coming from.
 * index
 *       -----------        -----------
 * 0     | item 0           | item 1 (displaced)
 *       -----------        -----------
 * 1     | item 1           | item 2 (displaced)
 *       -----------  --->  -----------      -----------
 * 2     | item 2                            | item 0 (dragged)
 *       -----------        -----------      -----------
 * 3     | item 3           | item 3
 *       -----------        -----------
 */
export function getDestinationPath<D>(
    flattenedTree: FlattenedTree<D>,
    sourceIndex: number,
    destinationIndex: number,
    // level on the tree, starting from 1.
    level?: number,
): Path {
    // Moving down
    const down: boolean = destinationIndex > sourceIndex;
    // Path of the source location
    const sourcePath: Path = getSourcePath(flattenedTree, sourceIndex);
    // Stayed at the same place
    const sameIndex: boolean = destinationIndex === sourceIndex;
    // Path of the upper item where the item was dropped
    const upperPath: Path = down
        ? flattenedTree[destinationIndex].path
        : flattenedTree[destinationIndex - 1] && flattenedTree[destinationIndex - 1].path;
    // Path of the lower item where the item was dropped
    const lowerPath: Path =
        down || sameIndex
            ? flattenedTree[destinationIndex + 1] && flattenedTree[destinationIndex + 1].path
            : flattenedTree[destinationIndex].path;

    /*
      We are going to differentiate 4 cases:
        - item didn't change position, only moved horizontally
        - item moved to the top of a list
        - item moved between two items on the same level
        - item moved to the end of list. This is an ambiguous case.
    */

    // Stayed in place, might moved horizontally
    if (sameIndex) {
        if (typeof level !== "number") {
            return sourcePath;
        }
        if (!upperPath) {
            // Not possible to move
            return sourcePath;
        }
        const minLevel = lowerPath ? lowerPath.length : 1;
        const maxLevel = Math.max(sourcePath.length, upperPath.length);
        const finalLevel = between(minLevel, maxLevel, level);
        const sameLevel: boolean = finalLevel === sourcePath.length;
        if (sameLevel) {
            // Didn't change level
            return sourcePath;
        }
        const previousPathOnTheFinalLevel: Path = getPathOnLevel(upperPath, finalLevel);
        return moveAfterPath(previousPathOnTheFinalLevel, sourcePath);
    }

    // Moved to top of the list
    if (lowerPath && isTopOfSubtree(lowerPath, upperPath)) {
        return lowerPath;
    }

    // Moved between two items on the same level
    if (upperPath && lowerPath && hasSameParent(upperPath, lowerPath)) {
        if (down && hasSameParent(upperPath, sourcePath)) {
            // if item was moved down within the list, it will replace the displaced item
            return upperPath;
        }
        return lowerPath;
    }

    // Moved to end of list
    if (upperPath) {
        // this means that the upper item is deeper in the tree.
        const finalLevel = calculateFinalLevel(sourcePath, upperPath, lowerPath, level);
        // Insert to higher levels
        const previousPathOnTheFinalLevel: Path = getPathOnLevel(upperPath, finalLevel);
        return moveAfterPath(previousPathOnTheFinalLevel, sourcePath);
    }

    // In case of any other impossible case
    return sourcePath;
}

/**
 * Translates a drag&drop movement from an index based position to a relative (parent, index) position
 */
export function calculateFinalDropPositions<D extends {}>(
    tree: ITreeData,
    flattenedTree: FlattenedTree<D>,
    dragState: IDragState,
): {
    sourcePosition: ITreeSourcePosition;
    destinationPosition?: ITreeDestinationPosition;
} {
    const { source, destination, combine, horizontalLevel } = dragState;
    const sourcePath: Path = getSourcePath(flattenedTree, source.index);
    const sourcePosition: ITreeSourcePosition = getTreePosition(tree, sourcePath);

    if (combine) {
        return {
            sourcePosition,
            destinationPosition: {
                parentId: combine.draggableId,
            },
        };
    }

    if (!destination) {
        return { sourcePosition, destinationPosition: undefined };
    }

    const destinationPath: Path = getDestinationPath(flattenedTree, source.index, destination.index, horizontalLevel);
    const destinationPosition: ITreeDestinationPosition = {
        ...getTreePosition(tree, destinationPath),
    };
    return { sourcePosition, destinationPosition };
}

/**
 * Changes the tree data structure with minimal reference changes.
 */
export function mutateTreeItem<D>(tree: ITreeData<D>, itemId: ItemID, mutation: ITreeItemMutation<D>): ITreeData<D> {
    const newTree = produce(tree, (treeDraft) => {
        const itemToChange = treeDraft.items[itemId];
        if (itemToChange) {
            treeDraft.items[itemId] = {
                ...itemToChange,
                ...castDraft(mutation),
            };
        }
    });
    return newTree;
}

/**
 * Changes the tree data structure with minimal reference changes.
 */
export function mutateTreeEvery<D>(tree: ITreeData<D>, mutation: ITreeItemMutation<D>): ITreeData<D> {
    // Returning a clone of the tree structure and overwriting the field coming in mutation
    return {
        // rootId should not change
        rootId: tree.rootId,
        items: Object.entries(tree.items).reduce((acc, [id, item]) => {
            acc[id] = {
                ...item,
                ...mutation,
            };
            return acc;
        }, {}),
    };
}

const hasLoadedChildren = <D>(item: ITreeItem<D>): boolean => !!item.hasChildren && item.children.length > 0;

const isLeafItem = <D>(item: ITreeItem<D>): boolean => !item.hasChildren;

/**
 * Return a new tree excluding the item at "position"
 */
export function removeItemFromTree<D>(
    tree: ITreeData<D>,
    position: ITreeSourcePosition,
    removeChildren: boolean = false,
): { tree: ITreeData<D>; itemRemoved: ItemID | null } {
    // Remove the item from its parents.

    // const newTree = produce(tree, (treeDraft) => {
    const sourceParent = tree.items[position.parentId];
    let newSourceChildren = [...sourceParent.children];
    let itemRemoved = newSourceChildren.splice(position.index, 1)[0];

    const item = tree.items[itemRemoved];
    if (item == null || itemRemoved == null) {
        return {
            itemRemoved: null,
            tree,
        };
    }

    if (removeChildren) {
        // Remove the children first.
        item.children.forEach((childID, i) => {
            tree = removeItemFromTree(tree, { parentId: item.id, index: i }, true).tree;
        });
    } else {
        newSourceChildren = [...newSourceChildren, ...item.children];
    }

    tree = mutateTreeItem(tree, position.parentId, {
        children: newSourceChildren,
        hasChildren: newSourceChildren.length > 0,
        isExpanded: newSourceChildren.length > 0 && sourceParent.isExpanded,
    });

    tree = produce(tree, (treeDraft) => {
        delete treeDraft.items[itemRemoved];
    });

    return {
        tree,
        itemRemoved: itemRemoved,
    };
}

/**
 * Find the parent ite
 */
export function findItemPosition<D>(tree: ITreeData<D>, itemID: ItemID): ITreeSourcePosition | null {
    for (const [loopItemID, loopItem] of Object.entries(tree.items)) {
        const index = loopItem.children.indexOf(itemID);
        if (index >= 0) {
            return {
                parentId: loopItemID,
                index,
            };
        }
    }

    return null;
}

/**
 * Return a new tree excluding the item at "position"
 */
export function removeItemFromTreeByID<D>(
    tree: ITreeData<D>,
    itemID: ItemID,
    removeChildren: boolean = false,
): { tree: ITreeData<D>; itemRemoved: ItemID | null } {
    const position = findItemPosition(tree, itemID);
    if (position === null) {
        return {
            tree,
            itemRemoved: null,
        };
    }

    return removeItemFromTree(tree, position, removeChildren);
}

/**
 * Return a new tree with the item at "from" moved to "to"
 */
export function moveItemOnTree<D>(
    tree: ITreeData<D>,
    from: ITreeSourcePosition,
    to: ITreeDestinationPosition,
): ITreeData<D> {
    return produce(tree, (treeDraft) => {
        const currentParent = treeDraft.items[from.parentId];
        const newParent = treeDraft.items[to.parentId];
        if (currentParent == null || newParent == null) {
            return;
        }
        const itemID = currentParent.children[from.index];
        if (itemID == null) {
            return;
        }

        // Remove from the old parent;
        currentParent.children.splice(from.index, 1);
        // Add into the new parent
        // Placing at the end if no index is specified.
        newParent.children.splice(to.index ?? newParent.children.length - 1, 0, itemID);
    });
}

/**
 * Find an item in a tree using it's id
 */
export const getItemById = <D>(flattenedTree: FlattenedTree<D>, id: ItemID): IFlattenedItem<D> | undefined =>
    flattenedTree.find((item) => item.item.id === id);

/**
 * Find the index of an item in a tree using it's id
 */
export const getIndexById = <D>(flattenedTree: FlattenedTree<D>, id: ItemID): number =>
    flattenedTree.findIndex((item) => item.item.id === id);

const TREE_ROOT_ID = "tree";

export type PartialTreeItem<T> = object | { id?: ItemID; children?: T[] };
export function itemsToTree<T extends PartialTreeItem<T>>(items: T[]): ITreeData<T> {
    const acc: Record<ItemID, ITreeItem<T>> = {};

    function itemAndChildrenToTree(item: PartialTreeItem<T>): ItemID {
        const children = ("children" in item && item.children ? item.children : []) ?? [];

        const childClearedData: T = { ...item, children: undefined } as T;
        delete childClearedData["children"];
        const treeItem: ITreeItem<T> = {
            id: ("id" in item ? item.id : undefined) ?? uuidv4(),
            children: [],
            hasChildren: children.length > 0,
            data: childClearedData,
        };

        acc[treeItem.id] = treeItem;

        children.forEach((item) => {
            const itemID = itemAndChildrenToTree(item);
            treeItem.children.push(itemID);
        });
        return treeItem.id;
    }

    itemAndChildrenToTree({
        id: TREE_ROOT_ID,
        children: items,
    });

    acc[TREE_ROOT_ID].data = undefined as any;

    return {
        rootId: TREE_ROOT_ID,
        items: acc,
    };
}

export type WithRecursiveChildren<D extends {}> = D & { children?: Array<WithRecursiveChildren<D>> };

export function treeToItems<D extends {}>(treeData: ITreeData<D>): Array<WithRecursiveChildren<D>> {
    function getWithChildren(item: ITreeItem<D>): WithRecursiveChildren<D> {
        const result: WithRecursiveChildren<D> = {
            ...item.data,
        };
        const children = item.children
            .map((childID) => {
                const item = treeData.items[childID];
                if (!item) {
                    console.warn(`Tree child with id ${childID} could not be found in tree data: `, treeData);
                    return null;
                }
                return getWithChildren(item);
            })
            .filter(notEmpty);
        if (children.length > 0) {
            result.children = children;
        }
        return result;
    }

    return getWithChildren(treeData.items[treeData.rootId]!).children ?? [];
}

export function getFirstItemID(treeData: ITreeData): ItemID | null {
    return treeData.items[TREE_ROOT_ID]?.children?.[0] ?? null;
}
