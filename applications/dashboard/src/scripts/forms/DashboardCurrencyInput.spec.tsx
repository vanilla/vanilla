import { useState } from "react";
import { RenderResult, act, fireEvent, render, screen } from "@testing-library/react";
import DashboardCurrencyInput from "@dashboard/forms/DashboardCurrencyInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";

function MockForm() {
    const [value, setValue] = useState<string | number>("0.00");
    return (
        <DashboardFormGroup labelType={DashboardLabelType.VERTICAL} label={"Currency"}>
            <DashboardCurrencyInput value={value} onChange={(newValue) => setValue(newValue)} onBlur={async () => {}} />
        </DashboardFormGroup>
    );
}

describe("DashboardCurrencyInput", () => {
    let result: RenderResult;
    let input: HTMLInputElement;

    async function renderDashboardCurrencyInput() {
        result = render(<MockForm />);
        await vi.dynamicImportSettled();
    }

    afterEach(() => {
        vitest.clearAllMocks();
    });

    function selectCurrencyInput(): HTMLInputElement {
        // I know this is very weird but spinbutton is the role of a number input
        return result.getByRole("spinbutton") as HTMLInputElement;
    }

    describe("when the input is rendered", () => {
        it("renders the input with default value 0.00", () => {
            renderDashboardCurrencyInput();
            expect(result.getByDisplayValue("0.00")).toBeInTheDocument();
        });

        it("renders the error messages", () => {
            const errors = [{ message: "Invalid input", field: "currency-input" }];
            render(
                <DashboardFormGroup labelType={DashboardLabelType.VERTICAL} label={"Currency"}>
                    <DashboardCurrencyInput value={1} onChange={() => {}} errors={errors} />
                </DashboardFormGroup>,
            );
            expect(screen.getByText("Invalid input")).toBeInTheDocument();
        });
    });

    describe("when the input is changed", () => {
        beforeEach(async () => {
            await renderDashboardCurrencyInput();
            input = selectCurrencyInput();
        });

        it("updates the value", async () => {
            fireEvent.change(input, { target: { value: 1.23 } });
            expect(input).toHaveDisplayValue("1.23");
        });

        it("trims extra decimal places", async () => {
            fireEvent.change(input, { target: { value: "1.2344" } });
            fireEvent.blur(input);

            expect(input).toHaveDisplayValue("1.23");
        });

        it("adds a decimal point if there isn't one", async () => {
            fireEvent.change(input, { target: { value: "123" } });
            fireEvent.blur(input);

            expect(input).toHaveDisplayValue("123.00");
        });

        it("adds a zero if the decimal point is the second to last character", async () => {
            fireEvent.change(input, { target: { value: "328.3" } });
            fireEvent.blur(input);

            expect(input).toHaveDisplayValue("328.30");
        });

        it("adds a leading zero if the first character is a decimal point", async () => {
            fireEvent.change(input, { target: { value: ".3" } });
            fireEvent.blur(input);

            expect(input).toHaveDisplayValue("0.30");
        });

        it("replaces the empty string with 0.00", async () => {
            fireEvent.change(input, { target: { value: "" } });
            fireEvent.blur(input);

            expect(input).toHaveDisplayValue("0.00");
        });
    });
});
