/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { act } from "@testing-library/react";
import { SynchronizationProvider, useSynchronizationContext } from "@library/vanilla-editor/SynchronizationContext";
import { renderHook } from "@testing-library/react-hooks";
import { MyValue } from "@library/vanilla-editor/typescript";

const MOCK_RICH2_SCHEMA = [{ type: "p", children: [{ text: "This is some unformatted text" }] }];
const MOCK_POST_HTML = "<p>This is some unformatted text</p>";

describe("SynchronizationContext", () => {
    let textarea;
    beforeEach(() => {
        textarea = document.createElement("textarea");
    });

    it("Sets initial value if format is rich2", () => {
        textarea.value = JSON.stringify(MOCK_RICH2_SCHEMA);
        const wrapper = ({ children }) => (
            <SynchronizationProvider textArea={textarea} initialFormat={"rich2"}>
                {children}
            </SynchronizationProvider>
        );
        const { result } = renderHook(() => useSynchronizationContext(), { wrapper });
        expect(result.current.initialValue).toEqual(MOCK_RICH2_SCHEMA);
        expect(result.current.initialHTML).toBeUndefined();
    });

    it("Initial value is undefined if format is not rich2", () => {
        textarea.value = JSON.stringify(MOCK_RICH2_SCHEMA);
        const wrapper = ({ children }) => (
            <SynchronizationProvider textArea={textarea} initialFormat={"anything-but-rich"}>
                {children}
            </SynchronizationProvider>
        );
        const { result } = renderHook(() => useSynchronizationContext(), { wrapper });
        expect(result.current.initialValue).toBeUndefined();
        expect(result.current.initialHTML).toBeUndefined();
    });

    it("Initial initialHTML is set if conversion is required", () => {
        textarea.value = MOCK_POST_HTML;
        const wrapper = ({ children }) => (
            <SynchronizationProvider textArea={textarea} initialFormat={"rich2"} needsHtmlConversion={true}>
                {children}
            </SynchronizationProvider>
        );
        const { result } = renderHook(() => useSynchronizationContext(), { wrapper });
        expect(result.current.initialValue).toBeUndefined();
        expect(result.current.initialHTML).toBe(MOCK_POST_HTML);
    });

    it("syncTextArea update textarea value", async () => {
        jest.useFakeTimers();
        const wrapper = ({ children }) => (
            <SynchronizationProvider textArea={textarea} initialFormat={"rich2"}>
                {children}
            </SynchronizationProvider>
        );
        const { result } = renderHook(() => useSynchronizationContext(), { wrapper });
        await act(async () => {
            result.current.syncTextArea(MOCK_RICH2_SCHEMA as MyValue);
            jest.advanceTimersByTime(2000);
            expect(textarea.value).toBe(JSON.stringify(MOCK_RICH2_SCHEMA));
        });
    });
});
