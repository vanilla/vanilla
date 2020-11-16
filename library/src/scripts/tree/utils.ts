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
    IFlattenedItem,
    ITreeItem,
    ITreeData,
    Path,
    IDragState,
    ITreeDestinationPosition,
    ITreeSourcePosition,
    ItemID,
    ITreeItemMutation,
} from "@library/tree/types";

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
export function calculateFinalDropPositions<D>(
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
    const itemToChange = tree.items[itemId];
    if (!itemToChange) {
        // Item not found
        return tree;
    }
    // Returning a clone of the tree structure and overwriting the field coming in mutation
    return {
        // rootId should not change
        rootId: tree.rootId,
        items: {
            // copy all old items
            ...tree.items,
            // overwriting only the item being changed
            [itemId]: {
                ...itemToChange,
                ...mutation,
            },
        },
    };
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
): { tree: ITreeData<D>; itemRemoved: ItemID } {
    const sourceParent = tree.items[position.parentId];
    const newSourceChildren = [...sourceParent.children];
    const itemRemoved = newSourceChildren.splice(position.index, 1)[0];
    const newTree = mutateTreeItem(tree, position.parentId, {
        children: newSourceChildren,
        hasChildren: newSourceChildren.length > 0,
        isExpanded: newSourceChildren.length > 0 && sourceParent.isExpanded,
    });

    return {
        tree: newTree,
        itemRemoved,
    };
}

function addItemToTree<D>(tree: ITreeData<D>, position: ITreeDestinationPosition, item: ItemID): ITreeData<D> {
    const destinationParent = tree.items[position.parentId];
    const newDestinationChildren = [...destinationParent.children];
    if (typeof position.index === "undefined") {
        if (hasLoadedChildren(destinationParent) || isLeafItem(destinationParent)) {
            newDestinationChildren.push(item);
        }
    } else {
        newDestinationChildren.splice(position.index, 0, item);
    }
    return mutateTreeItem(tree, position.parentId, {
        children: newDestinationChildren,
        hasChildren: true,
    });
}

/**
 * Return a new tree with the item at "from" moved to "to"
 */
export function moveItemOnTree<D>(
    tree: ITreeData<D>,
    from: ITreeSourcePosition,
    to: ITreeDestinationPosition,
): ITreeData<D> {
    const { tree: treeWithoutSource, itemRemoved } = removeItemFromTree(tree, from);
    return addItemToTree(treeWithoutSource, to, itemRemoved);
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
