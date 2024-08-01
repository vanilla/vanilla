/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useRef } from "react";
import { useFocusOnActivate } from "./useFocusOnActivate";
import { render, RenderResult } from "@testing-library/react";

function Example(props: { isActive: boolean }) {
    const ref = useRef<HTMLDivElement | null>(null);
    useFocusOnActivate(ref, props.isActive);
    return (
        <>
            <div tabIndex={-1} ref={ref} data-testid="example1">
                To focus
            </div>
            <div tabIndex={-1} data-testid="example2"></div>
        </>
    );
}

describe("useFocusOnActivate", () => {
    let tree: RenderResult;
    beforeEach(() => {
        tree = render(<></>);
    });
    afterEach(() => {
        tree.unmount();
    });

    it("gains focus on activate", () => {
        tree.rerender(<Example isActive={false} />);
        tree.rerender(<Example isActive={true} />);
        expect(document.activeElement).toBe(tree.getByTestId("example1"));
    });

    it("can focus initially on activate", () => {
        tree.rerender(<Example isActive={true} />);
        expect(document.activeElement).toBe(tree.getByTestId("example1"));
    });

    it("will not steal focus if it's not active", () => {
        tree.rerender(<Example isActive={false} />);
        tree.getByTestId("example2").focus();
        tree.rerender(<Example isActive={false} />);
        expect(document.activeElement).toBe(tree.getByTestId("example2"));
    });
});
