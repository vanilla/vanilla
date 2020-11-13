/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Path } from "./types";

/*
  Checking if two given path are equal
 */
export function isSamePath(a: Path, b: Path): boolean {
    if (a === b) {
        return true;
    }
    return a.length === b.length && a.every((v, i) => v === b[i]);
}

/*
  Checks if the two paths have the same parent
 */
export function hasSameParent(a: Path, b: Path): boolean {
    return isSamePath(getParentPath(a), getParentPath(b));
}

/*
  Calculates the parent path for a path
*/
export function getParentPath(child: Path): Path {
    return child.slice(0, child.length - 1);
}

/*
  It checks if the item is on top of a sub tree based on the two neighboring items, which are above or below the item.
*/
export function isTopOfSubtree(belowPath: Path, abovePath?: Path) {
    return !abovePath || isParentOf(abovePath, belowPath);
}

function isParentOf(parent: Path, child: Path): boolean {
    return isSamePath(parent, getParentPath(child));
}

export function getIndexAmongSiblings(path: Path): number {
    const lastIndex = path[path.length - 1];
    return lastIndex;
}

export function getPathOnLevel(path: Path, level: number): Path {
    return path.slice(0, level);
}

export function moveAfterPath(after: Path, from: Path): Path {
    const newPath: Path = [...after];
    const movedDownOnTheSameLevel = isLowerSibling(newPath, from);
    if (!movedDownOnTheSameLevel) {
        // not moved within the same subtree
        newPath[newPath.length - 1] += 1;
    }
    return newPath;
}

export function isLowerSibling(a: Path, other: Path): boolean {
    return hasSameParent(a, other) && getIndexAmongSiblings(a) > getIndexAmongSiblings(other);
}
