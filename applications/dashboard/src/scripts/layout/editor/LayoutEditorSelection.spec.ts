/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import {
    LayoutEditorDirection,
    LayoutEditorSelection,
    LayoutEditorSelectionMode,
    LayoutEditorSelectionState,
} from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutEditorFixture } from "@dashboard/layout/editor/__fixtures__/LayoutEditor.fixtures";
import { ILayoutEditorWidgetPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { spy } from "sinon";

describe("LayoutEditorSelection", () => {
    it("returns a new object instead of mutating", () => {
        const onChange = spy();
        const first = new LayoutEditorSelectionState(
            LayoutEditorPath.section(0),
            LayoutEditorSelectionMode.SECTION,
            onChange,
        );
        const second = first.setMode(LayoutEditorSelectionMode.NONE);
        const third = second.setMode(LayoutEditorSelectionMode.WIDGET);
        expect(first).not.toEqual(second);
        expect(second).not.toEqual(third);
        expect(onChange.getCalls()).toHaveLength(2);
    });

    it("can backup and restore state", () => {
        const first = new LayoutEditorSelectionState(
            LayoutEditorPath.section(0),
            LayoutEditorSelectionMode.SECTION,
            () => {},
        );
        const second = first.stashState();
        expect(second.getMode()).toBe(LayoutEditorSelectionMode.NONE);
        const third = second.restoreState();
        expect(third.getMode()).toBe(LayoutEditorSelectionMode.SECTION);
    });

    it("can move a section selection up and down", () => {
        let contents = LayoutEditorFixture.contents({
            section1: {},
            section2: {},
        });
        let selection: LayoutEditorSelection;
        selection = new LayoutEditorSelectionState(undefined, undefined, (newSelection) => {
            selection = newSelection.withContents(contents);
        }).withContents(contents);

        // Start on the first section.
        selection.moveSelectionTo(LayoutEditorPath.section(0), LayoutEditorSelectionMode.SECTION);
        expect(selection.getPath()).toStrictEqual(LayoutEditorPath.section(0));
        expect(selection.getMode()).toStrictEqual(LayoutEditorSelectionMode.SECTION);

        // Move up to the first add.
        selection.moveSelectionInDirection(LayoutEditorDirection.UP);
        expect(selection.getPath()).toStrictEqual(LayoutEditorPath.section(0));
        expect(selection.getMode()).toStrictEqual(LayoutEditorSelectionMode.SECTION_ADD);

        // Move down the second add.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        expect(selection.getPath()).toStrictEqual(LayoutEditorPath.section(1));
        expect(selection.getMode()).toStrictEqual(LayoutEditorSelectionMode.SECTION_ADD);

        // Move down the second section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        expect(selection.getPath()).toStrictEqual(LayoutEditorPath.section(1));
        expect(selection.getMode()).toStrictEqual(LayoutEditorSelectionMode.SECTION);

        // Move down last "virtual" add section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        expect(selection.getPath()).toStrictEqual(LayoutEditorPath.section(2));
        expect(selection.getMode()).toStrictEqual(LayoutEditorSelectionMode.SECTION_ADD);
    });

    it("can move widget selection directionally", () => {
        let contents = LayoutEditorFixture.contents({
            fullWidth: {
                $hydrate: "react.section.full-width",
                children: ["full1", "full2"],
            },
            fullWidthEmpty: {
                $hydrate: "react.section.full-width",
            },
            oneCol: {
                $hydrate: "react.section.1-column",
                children: ["oneCol1", "1Col2"],
            },
            oneColEmpty: {
                $hydrate: "react.section.1-column",
            },
            twoCol: {
                $hydrate: "react.section.2-columns",
                mainBottom: ["2Col1"],
            },
            threeCol: {
                $hydrate: "react.section.3-columns",
                leftBottom: ["3ColLeft1", "3Col2"],
                middleBottom: ["3ColMiddle"],
                rightBottom: ["3ColRight1", "3ColRight2"],
            },
        });
        let selection: LayoutEditorSelection;
        selection = new LayoutEditorSelectionState(undefined, undefined, (newSelection) => {
            selection = newSelection.withContents(contents);
        }).withContents(contents);

        function assertSelectedWidgetID(expected: string | null) {
            expect(selection.getMode()).toBe(LayoutEditorSelectionMode.WIDGET);
            const widget = contents.getWidget(selection.getPath());
            expect(widget?.["testID"] ?? null).toBe(expected);
        }

        function assertSelectAddButton(expectedPath: ILayoutEditorWidgetPath) {
            expect(selection.getMode()).toBe(LayoutEditorSelectionMode.WIDGET);
            expect(selection.getPath()).toStrictEqual(expectedPath);
            assertSelectedWidgetID(null);
        }

        // Start on the first section.
        selection.moveSelectionTo(LayoutEditorPath.section(0), LayoutEditorSelectionMode.WIDGET);
        assertSelectedWidgetID("full1");

        // Moving up/down/left doesn't do anything when those aren't available.
        selection.moveSelectionInDirection(LayoutEditorDirection.UP);
        selection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
        selection.moveSelectionInDirection(LayoutEditorDirection.RIGHT);
        assertSelectedWidgetID("full1");

        // We can move down within our section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectedWidgetID("full2");

        // We move into the empty section.
        // Full width sections don't have their own add button
        // so we end up in the next sections add button.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectAddButton(LayoutEditorPath.widget(1, "children", 0));

        // We can move down and through the 1col section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectedWidgetID("1Col2");

        // We move into the first 1col secitons add button.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectAddButton(LayoutEditorPath.widget(2, "children", 2));

        // We move into the next empty 1col section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectAddButton(LayoutEditorPath.widget(3, "children", 0));

        // Moving left/right doesn't do anything in a 1col section.
        selection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
        assertSelectAddButton(LayoutEditorPath.widget(3, "children", 0));
        selection.moveSelectionInDirection(LayoutEditorDirection.RIGHT);
        assertSelectAddButton(LayoutEditorPath.widget(3, "children", 0));

        // We can move into the 2 column section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectedWidgetID("2Col1");

        // We can move into the main columns "add widget".
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectAddButton(LayoutEditorPath.widget(4, "mainBottom", 1));

        // Going left doesn't do anything because we are already left.
        selection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
        assertSelectAddButton(LayoutEditorPath.widget(4, "mainBottom", 1));

        // We can move right into the secondary column.
        selection.moveSelectionInDirection(LayoutEditorDirection.RIGHT);
        assertSelectAddButton(LayoutEditorPath.widget(4, "secondaryBottom", 0));

        // Going right doesn't do anything.
        selection.moveSelectionInDirection(LayoutEditorDirection.RIGHT);
        assertSelectAddButton(LayoutEditorPath.widget(4, "secondaryBottom", 0));

        // We can move down into the 3 column section.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectedWidgetID("3ColRight1");

        // We can move 2 the second item in the right column.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectedWidgetID("3ColRight2");

        // We can move into the add button for the right column.
        selection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
        assertSelectAddButton(LayoutEditorPath.widget(5, "rightBottom", 2));

        // Going right doesn't do anything.
        selection.moveSelectionInDirection(LayoutEditorDirection.RIGHT);
        assertSelectAddButton(LayoutEditorPath.widget(5, "rightBottom", 2));

        // We can move into the main column.
        selection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
        assertSelectAddButton(LayoutEditorPath.widget(5, "middleBottom", 1));
        selection.moveSelectionInDirection(LayoutEditorDirection.UP);
        assertSelectedWidgetID("3ColMiddle");

        // We can move left into the left column.
        selection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
        assertSelectedWidgetID("3ColLeft1");

        // Going left doesn't doing anything.
        selection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
        assertSelectedWidgetID("3ColLeft1");

        // We can move back up into the 2 column
        selection.moveSelectionInDirection(LayoutEditorDirection.UP);
        assertSelectAddButton(LayoutEditorPath.widget(4, "mainBottom", 1));
        selection.moveSelectionInDirection(LayoutEditorDirection.UP);
        assertSelectedWidgetID("2Col1");
    });
});
