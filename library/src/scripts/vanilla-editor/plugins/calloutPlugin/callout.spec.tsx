/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createVanillaEditor } from "@library/vanilla-editor/createVanillaEditor";
import { VanillaEditorFormatter } from "@library/vanilla-editor/VanillaEditorFormatter";
import {
    ELEMENT_CALLOUT,
    ELEMENT_CALLOUT_ITEM,
} from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
import { ELEMENT_PARAGRAPH } from "@udecode/plate-paragraph";
import { ELEMENT_SPOILER } from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { ELEMENT_BLOCKQUOTE } from "@udecode/plate-block-quote";
import { vi } from "vitest";
import { render, screen } from "@testing-library/react";
import { CalloutToolbar } from "@library/vanilla-editor/plugins/calloutPlugin/CalloutToolbar";
import { PlateProvider } from "@udecode/plate-common";
import { VanillaEditorBoundsContext } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { Plate } from "@udecode/plate-core";
import { MyValue } from "@library/vanilla-editor/typescript";

// Mock focusEditor to avoid DOM issues in tests
vi.mock("@udecode/plate-common", async () => ({
    ...(await vi.importActual("@udecode/plate-common")),
    focusEditor: vi.fn(),
    toDOMNode: vi.fn(() => ({
        getBoundingClientRect: () => ({
            top: 100,
            left: 50,
            width: 200,
            height: 40,
            right: 250,
            bottom: 140,
        }),
    })),
}));

// Mock DOM methods that are used in CalloutToolbar
Object.defineProperty(HTMLElement.prototype, "getBoundingClientRect", {
    value: vi.fn(() => ({
        top: 100,
        left: 50,
        width: 200,
        height: 40,
        right: 250,
        bottom: 140,
    })),
});

describe("Callout Plugin", () => {
    it("transforms paragraph into callout", () => {
        const editor = createVanillaEditor();
        const formatter = new VanillaEditorFormatter(editor);

        // Insert a paragraph
        editor.insertNode({
            type: ELEMENT_PARAGRAPH,
            children: [{ text: "This is a paragraph" }],
        });

        editor.select({
            anchor: { path: [0, 0], offset: 0 },
            focus: { path: [0, 0], offset: 19 },
        });

        // Transform to callout
        formatter.callout();

        expect(formatter.isCallout()).toBe(true);
        expect(formatter.calloutAppearance()).toBe("neutral");
        expect(editor.children[0]).toMatchObject({
            type: ELEMENT_CALLOUT,
            appearance: "neutral",
            children: [
                {
                    type: ELEMENT_CALLOUT_ITEM,
                    children: [{ text: "This is a paragraph" }],
                },
            ],
        });
    });

    it("transforms callout back to paragraph", () => {
        const editor = createVanillaEditor();
        const formatter = new VanillaEditorFormatter(editor);

        // Insert a callout
        editor.insertNode({
            type: ELEMENT_CALLOUT,
            appearance: "info",
            children: [
                {
                    type: ELEMENT_CALLOUT_ITEM,
                    children: [{ text: "This is a callout" }],
                },
            ],
        });

        editor.select({
            anchor: { path: [0, 0, 0], offset: 0 },
            focus: { path: [0, 0, 0], offset: 17 },
        });

        // Transform to paragraph
        formatter.paragraph();

        expect(formatter.isParagraph()).toBe(true);
        expect(formatter.isCallout()).toBe(false);
        expect(editor.children[0]).toMatchObject({
            type: ELEMENT_PARAGRAPH,
            children: [{ text: "This is a callout" }],
        });
    });

    it("transforms spoiler into callout", () => {
        const editor = createVanillaEditor();
        const formatter = new VanillaEditorFormatter(editor);

        // Insert a spoiler
        editor.insertNode({
            type: ELEMENT_SPOILER,
            children: [
                {
                    type: ELEMENT_PARAGRAPH,
                    children: [{ text: "This is a spoiler" }],
                },
            ],
        });

        editor.select({
            anchor: { path: [0, 0, 0], offset: 0 },
            focus: { path: [0, 0, 0], offset: 17 },
        });

        // Transform to callout
        formatter.callout();

        expect(formatter.isCallout()).toBe(true);
        expect(formatter.isSpoiler()).toBe(false);
        expect(editor.children[0]).toMatchObject({
            type: ELEMENT_CALLOUT,
            appearance: "neutral",
            children: [
                {
                    type: ELEMENT_CALLOUT_ITEM,
                    children: [{ text: "This is a spoiler" }],
                },
            ],
        });
    });

    it("transforms callout into blockquote", () => {
        const editor = createVanillaEditor();
        const formatter = new VanillaEditorFormatter(editor);

        // Insert a callout
        editor.insertNode({
            type: ELEMENT_CALLOUT,
            appearance: "warning",
            children: [
                {
                    type: ELEMENT_CALLOUT_ITEM,
                    children: [{ text: "This is a callout" }],
                },
            ],
        });

        editor.select({
            anchor: { path: [0, 0, 0], offset: 0 },
            focus: { path: [0, 0, 0], offset: 17 },
        });

        // Transform to blockquote
        formatter.blockquote();

        expect(formatter.isBlockquote()).toBe(true);
        expect(formatter.isCallout()).toBe(false);
        expect(editor.children[0]).toMatchObject({
            type: ELEMENT_BLOCKQUOTE,
            children: [
                {
                    type: "blockquote-line",
                    children: [{ text: "This is a callout" }],
                },
            ],
        });
    });

    it("shows callout toolbar when selection is in callout", () => {
        const editor = createVanillaEditor();

        // Insert a callout
        editor.insertNode({
            type: ELEMENT_CALLOUT,
            appearance: "info",
            children: [
                {
                    type: ELEMENT_CALLOUT_ITEM,
                    children: [{ text: "This is a callout" }],
                },
            ],
        });

        // Select inside the callout
        editor.select({
            anchor: { path: [0, 0, 0], offset: 0 },
            focus: { path: [0, 0, 0], offset: 17 },
        });

        // Render the CalloutToolbar with proper context structure
        render(
            <TestReduxProvider>
                <PlateProvider<MyValue> id="test-editor" editor={editor} initialValue={editor.children as MyValue}>
                    <VanillaEditorBoundsContext>
                        <Plate<MyValue> id="test-editor" editor={editor}>
                            <CalloutToolbar />
                        </Plate>
                    </VanillaEditorBoundsContext>
                </PlateProvider>
            </TestReduxProvider>,
        );

        // Check that toolbar buttons are present
        expect(screen.getByLabelText("Neutral")).toBeInTheDocument();
        expect(screen.getByLabelText("Info")).toBeInTheDocument();
        expect(screen.getByLabelText("Warning")).toBeInTheDocument();
        expect(screen.getByLabelText("Alert")).toBeInTheDocument();
    });
});
