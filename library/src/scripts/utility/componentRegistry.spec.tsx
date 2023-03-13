/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { IHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.types";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { act, render, screen } from "@testing-library/react";
import React, { Suspense } from "react";
import "@testing-library/jest-dom/extend-expect";

const mockLayout: IHydratedLayoutWidget[] = [
    {
        $reactComponent: "LazyComponent",
        $reactProps: {},
    },
];

function Faux() {
    return (
        <Suspense fallback={"I am still loading"}>
            <WidgetLayout>
                <LayoutRenderer layout={mockLayout} />
            </WidgetLayout>
        </Suspense>
    );
}

describe("registerLoadableWidgets", () => {
    beforeAll(() => {
        return registerLoadableWidgets({
            LazyComponent: () => import("./__fixtures__/MockLazyComponent"),
        });
    });

    it("show fallback before lazy component loads", async () => {
        await act(async () => {
            render(<Faux />);
        });
        const mockLazyWidget = screen.queryByText(/I am the lazy component/i);
        expect(mockLazyWidget).not.toBeInTheDocument();
    });

    it("loads the lazy component", async () => {
        await act(async () => {
            render(<Faux />);
        });
        const mockLazyWidget = await screen.findByText(/I am the lazy component/i);
        expect(mockLazyWidget).toBeInTheDocument();
    });
});
