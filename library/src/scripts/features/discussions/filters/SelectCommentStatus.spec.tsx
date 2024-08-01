/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import { render, screen, act, RenderResult, fireEvent, cleanup } from "@testing-library/react";
import SelectCommentStatus from "@library/features/discussions/filters/SelectCommentStatus";

describe("Select comment status", async () => {
    let result: RenderResult;
    let hasNoCommentsCheckbox: HTMLElement;
    let hasCommentsCheckbox: HTMLElement;
    const mockOnChange = vitest.fn();

    async function renderSelectCommentStatus(value: boolean | undefined = undefined) {
        result = render(<SelectCommentStatus label="Post enagement" value={value} onChange={mockOnChange} />);

        await vi.dynamicImportSettled();
        hasNoCommentsCheckbox = await screen.findByRole("checkbox", { name: "No Comments" });
        hasCommentsCheckbox = await screen.findByRole("checkbox", { name: "Has Comments" });
    }

    afterEach(() => {
        vitest.resetAllMocks();
        cleanup();
    });

    it("should render with both checkboxes checked when the value is undefined", async () => {
        await renderSelectCommentStatus(undefined);
        expect(hasNoCommentsCheckbox).toBeChecked();
        expect(hasCommentsCheckbox).toBeChecked();
    });

    it("should render with the 'Has no comments' checkbox checked when the value is false", async () => {
        await renderSelectCommentStatus(false);
        expect(hasNoCommentsCheckbox).toBeChecked();
        expect(hasCommentsCheckbox).not.toBeChecked();
    });

    it("should render with the 'Has comments' checkbox checked when the value is true", async () => {
        await renderSelectCommentStatus(true);
        expect(hasNoCommentsCheckbox).not.toBeChecked();
        expect(hasCommentsCheckbox).toBeChecked();
    });

    it("should call the onChange function with true when the 'Has no comments' checkbox is unchecked", async () => {
        await renderSelectCommentStatus(undefined);
        await act(async () => {
            fireEvent.click(hasNoCommentsCheckbox);
        });
        expect(mockOnChange).toHaveBeenCalledWith(true);
    });

    it("should call the onChange function with false when the 'Has comments' checkbox is unchecked", async () => {
        await renderSelectCommentStatus(undefined);
        await act(async () => {
            fireEvent.click(hasCommentsCheckbox);
        });
        expect(mockOnChange).toHaveBeenCalledWith(false);
    });
});
