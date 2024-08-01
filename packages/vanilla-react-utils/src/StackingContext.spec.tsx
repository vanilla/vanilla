/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, screen } from "@testing-library/react";
import React from "react";
import { StackingContextProvider, useStackingContext } from "./StackingContext";

function DisplayCurrentZindex(props) {
    const { zIndex } = useStackingContext();
    return (
        <>
            <span data-testid={props.testid}>{zIndex}</span>
            {props.children}
        </>
    );
}

describe("Stacking Context", () => {
    it("zIndex begins at 1051", () => {
        render(
            <StackingContextProvider>
                <DisplayCurrentZindex testid={"zindex"} />
            </StackingContextProvider>,
        );
        expect(screen.getByTestId("zindex")).toHaveTextContent("1051");
    });

    it("Nested providers increment the zIndex", () => {
        render(
            <StackingContextProvider>
                <DisplayCurrentZindex testid={"zindex-1"}>
                    <StackingContextProvider>
                        <DisplayCurrentZindex testid={"zindex-2"} />
                    </StackingContextProvider>
                </DisplayCurrentZindex>
            </StackingContextProvider>,
        );
        expect(screen.getByTestId("zindex-2")).toHaveTextContent("1052");
    });
});
