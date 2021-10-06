/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ItemID, ITreeData } from "@library/tree/types";
import {
    getFirstItemID,
    itemsToTree,
    moveItemOnTree,
    mutateTreeEvery,
    mutateTreeItem,
    removeItemFromTreeByID,
    treeToItems,
    WithRecursiveChildren,
} from "@library/tree/utils";

type MyData = {
    id?: string;
    foo: string;
};
const flatItems: MyData[] = [
    {
        id: "helloId",
        foo: "hello",
    },
    {
        foo: "world",
        id: "worldId",
    },
];
Object.freeze(flatItems);

const flatTree: ITreeData<MyData> = {
    rootId: "tree",
    items: {
        tree: {
            children: ["helloId", "worldId"],
            id: "tree",
            hasChildren: true,
            data: undefined as any,
        },
        helloId: {
            children: [],
            id: "helloId",
            data: flatItems[0],
            hasChildren: false,
        },
        worldId: {
            children: [],
            id: "worldId",
            data: flatItems[1],
            hasChildren: false,
        },
    },
};
Object.freeze(flatTree);

const nestedItems: Array<WithRecursiveChildren<MyData>> = [
    {
        id: "helloId",
        foo: "hello",
        children: [
            {
                foo: "world",
                id: "worldId",
                children: [
                    {
                        foo: "!",
                        id: "exlamId",
                        children: undefined,
                    },
                ],
            },
        ],
    },
];
Object.freeze(nestedItems);

const nestedTree: ITreeData<MyData> = {
    rootId: "tree",
    items: {
        exlamId: {
            id: "exlamId",
            children: [],
            data: {
                foo: "!",
                id: "exlamId",
            },
            hasChildren: false,
        },
        tree: {
            children: ["helloId"],
            id: "tree",
            data: undefined as any,
            hasChildren: true,
        },
        worldId: {
            children: ["exlamId"],
            id: "worldId",
            data: {
                foo: "world",
                id: "worldId",
            },
            hasChildren: true,
        },
        helloId: {
            children: ["worldId"],
            id: "helloId",
            data: {
                id: "helloId",
                foo: "hello",
            },
            hasChildren: true,
        },
    },
};
Object.freeze(nestedTree);

describe("Item <-> Conversion", () => {
    describe("itemsToTree()", () => {
        it("can convert flat items to a tree", () => {
            expect(itemsToTree(flatItems)).toEqual(flatTree);
        });

        it("can convert nested items to a tree", () => {
            expect(itemsToTree(nestedItems)).toEqual(nestedTree);
        });
    });

    describe("treeToItems()", () => {
        it("can convert a flat tree to flat items", () => {
            expect(treeToItems(flatTree)).toEqual(flatItems);
        });

        it("can convert a nested tree to nested items", () => {
            expect(treeToItems(nestedTree)).toEqual(nestedItems);
        });
    });
});

describe("tree modification", () => {
    describe("mutateTreeItem", () => {
        it("can modify a tree item", () => {
            const mutated = mutateTreeItem(nestedTree, "worldId", {
                data: {
                    ...nestedTree.items.worldId.data,
                    foo: "bar",
                },
            });
            expect(mutated.items["worldId"].data).toEqual({
                id: "worldId",
                foo: "bar",
            });
        });

        it("can completely override a tree item", () => {
            const mutated = mutateTreeItem(nestedTree, "worldId", {
                data: {
                    foo: "bar",
                },
            });
            expect(mutated.items["worldId"].data).toEqual({
                foo: "bar",
            });
        });
    });

    describe("mutateEvery", () => {
        it("can modify every tree item", () => {
            const mutated = mutateTreeEvery(nestedTree, {
                isExpanded: true,
            });
            expect(Object.values(mutated.items).map((item) => item.isExpanded)).toEqual([true, true, true, true]);
        });
    });

    describe("removeItemFromTreeByID", () => {
        it("can remove an item by it's ID", () => {
            const removed = removeItemFromTreeByID(flatTree, "helloId");
            expect(treeToChildMap(removed.tree)).toEqual({ tree: ["worldId"], worldId: [] });
        });

        it("can remove an item and preserve it's children", () => {
            const removed = removeItemFromTreeByID(nestedTree, "helloId", false);
            // expect(treeToChildMap(nestedTree)).toEqual({});

            // Starts as
            // tree
            //   helloId DELETE
            //     worldId
            //       exlamId

            expect(treeToChildMap(removed.tree)).toEqual({
                tree: ["worldId"],
                worldId: ["exlamId"],
                exlamId: [],
            });
        });

        it("can remove an item and delete it's children", () => {
            const removed = removeItemFromTreeByID(nestedTree, "helloId", true);

            // Starts as
            // tree
            //   helloId DELETE
            //     worldId CHILD DELETE
            //       exlamId CHILD DELETE

            expect(treeToChildMap(removed.tree)).toEqual({
                tree: [],
            });
        });
    });

    describe("moveItemOnTree", () => {
        it("can move an item within it's parent", () => {
            const initial = treeToChildMap(flatTree);
            // Starts
            // hello, world
            const newTree = moveItemOnTree(
                flatTree,
                {
                    parentId: "tree",
                    index: 0,
                },
                {
                    parentId: "tree",
                    index: 1,
                },
            );
            // Ends
            // world, hello
            expect(treeToChildMap(newTree)).toEqual({
                ...initial,
                tree: ["worldId", "helloId"],
            });
        });
        it("can move child to the root with its children", () => {
            const newTree = moveItemOnTree(
                nestedTree,
                {
                    parentId: "helloId",
                    index: 0,
                },
                {
                    parentId: "tree",
                    index: 0,
                },
            );
            expect(treeToChildMap(newTree)).toEqual({
                tree: ["worldId", "helloId"],
                helloId: [],
                worldId: ["exlamId"],
                exlamId: [],
            });
        });
    });
});

function treeToChildMap(tree: ITreeData): Record<ItemID, ItemID[]> {
    const idsToChildren = Object.fromEntries(
        Object.entries(tree.items).map(([id, item]) => {
            return [id, item.children];
        }),
    );
    return idsToChildren;
}
