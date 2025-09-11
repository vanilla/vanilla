import { useState } from "react";
import { RenderResult, fireEvent, render } from "@testing-library/react";
import DashboardRatioInput from "@dashboard/forms/DashboardRatioInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "./DashboardLabelType";

function MockForm() {
    const [value, setValue] = useState(35);
    return (
        <DashboardFormGroup labelType={DashboardLabelType.VERTICAL} label={"Ratio"}>
            <DashboardRatioInput value={value} onChange={(newValue) => setValue(newValue)} />
        </DashboardFormGroup>
    );
}

describe("DashboardRatioInput", () => {
    let result: RenderResult;
    let input: HTMLElement;

    beforeEach(async () => {
        result = render(<MockForm />);
        await vi.dynamicImportSettled();
    });

    afterEach(() => {
        vitest.clearAllMocks();
    });

    it("renders the component with default value 35, displays '1 in 35'", () => {
        expect(result.getByText("1")).toBeInTheDocument();
        expect(result.getByText("in")).toBeInTheDocument();
        expect(result.getByDisplayValue("35")).toBeInTheDocument();
    });

    it("allows users to update the value of the second input", () => {
        input = result.getByDisplayValue("35");
        fireEvent.change(input, { target: { value: "10" } });
        expect(input).toHaveValue(10);
    });

    it("does not allow decimals", () => {
        input = result.getByDisplayValue("35");
        fireEvent.change(input, { target: { value: "5.9" } });
        expect(input).toHaveValue(5);
    });

    it("does not allow zero, replaces it with default value of 1", () => {
        input = result.getByDisplayValue("35");
        fireEvent.change(input, { target: { value: "0" } });
        expect(input).toHaveValue(1);
    });

    it("replaces the empty string with default value of 1", () => {
        input = result.getByDisplayValue("35");
        fireEvent.change(input, { target: { value: "" } });
        expect(input).toHaveValue(1);
    });
});
