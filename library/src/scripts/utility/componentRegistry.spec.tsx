/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { IHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.types";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { act, render, screen, waitFor } from "@testing-library/react";
import React, { Suspense } from "react";
import "@testing-library/jest-dom";

const mockLayout: IHydratedLayoutWidget[] = [
    {
        $reactComponent: "LazyComponent",
        $reactProps: {},
    },
];

function Faux() {
    return (
        <WidgetLayout>
            <LayoutRenderer fallback={<div data-testid="loading">I am still loading</div>} layout={mockLayout} />
        </WidgetLayout>
    );
}

describe("registerLoadableWidgets", () => {
    beforeAll(() => {
        return registerLoadableWidgets({
            LazyComponent: () =>
                new Promise((resolve) => {
                    setTimeout(() => {
                        resolve(import("./__fixtures__/MockLazyComponent"));
                    }, 500);
                }),
        });
    });

    it("loads the lazy component", async () => {
        render(<Faux />);

        const loading = await screen.findByTestId("loading");
        const loaded = await screen.findByTestId("loaded");

        expect(loaded).toBeInTheDocument();
        expect(loading).not.toBeInTheDocument();
    });
});
