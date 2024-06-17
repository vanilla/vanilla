/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FilterBlock } from "@dashboard/moderation/components/FilterBlock";
import { mockAPI } from "@library/__tests__/utility";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import MockAdapter from "axios-mock-adapter";
import { ComponentProps } from "react";

function renderFilterBlock(props?: Partial<ComponentProps<typeof FilterBlock>>) {
    render(
        // This ignore because component props are not static
        // @ts-ignore
        <FilterBlock
            apiName={"apiName"}
            label={"Mock Filter Label"}
            initialFilters={[]}
            staticOptions={[
                {
                    name: "Mock Option",
                    value: "mock-option",
                },
            ]}
            onFilterChange={() => null}
            {...props}
        />,
    );
}

describe("FilterBlock", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(/mock-api.*/).reply(200, [{ valueKey: "mock-option", labelKey: "Mock Option" }]);
    });
    it("Displays static options", () => {
        renderFilterBlock({
            staticOptions: [
                {
                    name: "Option 1",
                    value: "option-1",
                },
                {
                    name: "Option 2",
                    value: "option-2",
                },
            ],
        });
        expect(screen.getByText("Option 1")).toBeInTheDocument();
        expect(screen.getByText("Option 2")).toBeInTheDocument();
    });
    it("Calls onFilterChange when a filter is selected", () => {
        const onFilterChange = vi.fn();
        renderFilterBlock({ onFilterChange });
        fireEvent.click(screen.getByText("Mock Option"));
        waitFor(() => expect(onFilterChange).toHaveBeenCalled());
    });
    it("Displays initial selected static filters", async () => {
        renderFilterBlock({
            initialFilters: ["mock-option"],
        });
        await waitFor(() => expect(screen.getByRole("checkbox")).toBeChecked());
    });
    it("Displays initial selected dynamic filters", async () => {
        renderFilterBlock({
            initialFilters: ["mock-option"],
            staticOptions: undefined,
            dynamicOptionApi: {
                searchUrl: "/mock-api",
                singleUrl: "/mock-api/%s",
                valueKey: "valueKey",
                labelKey: "labelKey",
            },
        });

        await waitFor(() => expect(expect(screen.getByText("Mock Option")).toBeInTheDocument()));
    });
});
