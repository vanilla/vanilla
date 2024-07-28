import { useState } from "react";
import { RenderResult, fireEvent, render, screen } from "@testing-library/react";
import { DashboardCurrencyInput } from "@dashboard/forms/DashboardCurrencyInput";

function MockForm() {
    const [value, setValue] = useState("0.00");

    return (
        <DashboardCurrencyInput
            inputProps={{
                value: value,
                onChange: (event) => setValue(event.target.value),
            }}
        />
    );
}

describe("DashboardCurrencyInput", () => {
    let result: RenderResult;
    let input: HTMLElement;

    async function renderDashboardCurrencyInput() {
        result = render(<MockForm />);
        await vi.dynamicImportSettled();
    }

    afterEach(() => {
        vitest.clearAllMocks();
    });

    describe("when the input is rendered", () => {
        it("renders the input with default value 0.00", () => {
            renderDashboardCurrencyInput();
            expect(result.getByDisplayValue("0.00")).toBeInTheDocument();
        });

        it("renders the error messages", () => {
            const errors = [{ message: "Invalid input", field: "currency-input" }];
            render(<DashboardCurrencyInput errors={errors} />);
            expect(screen.getByText("Invalid input")).toBeInTheDocument();
        });
    });

    describe("when the input is changed", () => {
        it("updates the value", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: "1.23" } });

            expect(input).toHaveValue("1.23");
        });

        it("trims extra decimal places", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: "1.2344" } });
            fireEvent.blur(input);

            expect(input).toHaveValue("1.23");
        });

        it("adds a decimal point if there isn't one", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: "123" } });
            fireEvent.blur(input);

            expect(input).toHaveValue("123.00");
        });

        it("adds two zeros if the decimal point is the last character", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: "328." } });
            fireEvent.blur(input);

            expect(input).toHaveValue("328.00");
        });

        it("adds a zero if the decimal point is the second to last character", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: "328.3" } });
            fireEvent.blur(input);

            expect(input).toHaveValue("328.30");
        });

        it("adds a leading zero if the first character is a decimal point", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: ".3" } });
            fireEvent.blur(input);

            expect(input).toHaveValue("0.30");
        });

        it("replaces the empty string with 0.00", async () => {
            renderDashboardCurrencyInput();

            input = result.getByDisplayValue("0.00");

            fireEvent.change(input, { target: { value: "" } });
            fireEvent.blur(input);

            expect(input).toHaveValue("0.00");
        });
    });
});
