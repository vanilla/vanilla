/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { SteppedModalFooter } from "@library/features/discussions/forms/PostSettingsSteppedModalFooter";
import { render, screen, fireEvent } from "@testing-library/react";

describe("SteppedModalFooter", () => {
    const baseProps = {
        isFirstStep: false,
        isFinalStep: false,
        onCancel: vi.fn(),
        onBack: vi.fn(),
        onNext: vi.fn(),
        onFinalize: vi.fn(),
        finalizeLabel: "Save Changes",
        disable: false,
        loading: false,
    };

    it("renders the cancel button on first step", () => {
        const props = {
            ...baseProps,
            isFirstStep: true,
            isFinalStep: false,
            onCancel: vi.fn(),
            onBack: vi.fn(),
            onNext: vi.fn(),
            onFinalize: vi.fn(),
            finalizeLabel: "Save Changes",
            disable: false,
            loading: false,
        };

        render(<SteppedModalFooter {...props} />);

        const cancelButton = screen.getByText("Cancel");
        expect(cancelButton).toBeInTheDocument();

        // Next button should be present since we're not on the final step
        const nextButton = screen.getByText("Next");
        expect(nextButton).toBeInTheDocument();

        // Back button should not be present on the first step
        expect(screen.queryByText("Back")).not.toBeInTheDocument();
    });

    it("renders the back button when not on first step", () => {
        const props = {
            ...baseProps,
            isFirstStep: false,
            isFinalStep: false,
            onCancel: vi.fn(),
            onBack: vi.fn(),
            onNext: vi.fn(),
            onFinalize: vi.fn(),
            finalizeLabel: "Save Changes",
            disable: false,
            loading: false,
        };

        render(<SteppedModalFooter {...props} />);

        const backButton = screen.getByText("Back");
        expect(backButton).toBeInTheDocument();

        // Cancel button should not be present when not on the first step
        expect(screen.queryByText("Cancel")).not.toBeInTheDocument();
    });

    it("renders the next button when not on final step", () => {
        const props = {
            ...baseProps,
            isFirstStep: false,
            isFinalStep: false,
            onCancel: vi.fn(),
            onBack: vi.fn(),
            onNext: vi.fn(),
            onFinalize: vi.fn(),
            finalizeLabel: "Save Changes",
            disable: false,
            loading: false,
        };

        render(<SteppedModalFooter {...props} />);

        const nextButton = screen.getByText("Next");
        expect(nextButton).toBeInTheDocument();

        // The finalize button should not be present when not on the final step
        expect(screen.queryByText("Save Changes")).not.toBeInTheDocument();
    });

    it("renders the finalize button on final step", () => {
        const props = {
            ...baseProps,
            isFirstStep: false,
            isFinalStep: true,
            onCancel: vi.fn(),
            onBack: vi.fn(),
            onNext: vi.fn(),
            onFinalize: vi.fn(),
            finalizeLabel: "Save Changes",
            disable: false,
            loading: false,
        };

        render(<SteppedModalFooter {...props} />);

        const finalizeButton = screen.getByText("Save Changes");
        expect(finalizeButton).toBeInTheDocument();

        // The next button should not be present on the final step
        expect(screen.queryByText("Next")).not.toBeInTheDocument();
    });

    it("shows a loader when loading is true", () => {
        const props = {
            ...baseProps,
            isFirstStep: false,
            isFinalStep: true,
            onCancel: vi.fn(),
            onBack: vi.fn(),
            onNext: vi.fn(),
            onFinalize: vi.fn(),
            finalizeLabel: "Save Changes",
            disable: false,
            loading: true,
        };

        render(<SteppedModalFooter {...props} />);

        // The text should not be present when loading
        expect(screen.queryByText("Save Changes")).not.toBeInTheDocument();

        // We can't easily test for the loader component itself as it doesn't have text,
        // but we can check that the button is present
        const buttons = screen.getAllByRole("button");
        expect(buttons.length).toBe(2); // Back and Save button
    });

    it("calls the appropriate functions when buttons are clicked", () => {
        const mockFunctions = {
            onCancel: vi.fn(),
            onBack: vi.fn(),
            onNext: vi.fn(),
            onFinalize: vi.fn(),
        };

        // First step
        const firstStepProps = {
            ...baseProps,
            ...mockFunctions,
            isFirstStep: true,
            isFinalStep: false,
        };

        const { unmount } = render(<SteppedModalFooter {...firstStepProps} />);

        fireEvent.click(screen.getByText("Cancel"));
        expect(mockFunctions.onCancel).toHaveBeenCalledTimes(1);

        fireEvent.click(screen.getByText("Next"));
        expect(mockFunctions.onNext).toHaveBeenCalledTimes(1);

        unmount();

        // Middle step
        const middleStepProps = {
            ...baseProps,
            ...mockFunctions,
            isFirstStep: false,
            isFinalStep: false,
        };

        const { unmount: unmount2 } = render(<SteppedModalFooter {...middleStepProps} />);

        fireEvent.click(screen.getByText("Back"));
        expect(mockFunctions.onBack).toHaveBeenCalledTimes(1);

        fireEvent.click(screen.getByText("Next"));
        expect(mockFunctions.onNext).toHaveBeenCalledTimes(2);

        unmount2();

        // Final step
        const finalStepProps = {
            ...baseProps,
            ...mockFunctions,
            isFirstStep: false,
            isFinalStep: true,
            finalizeLabel: "Save Changes",
        };

        render(<SteppedModalFooter {...finalStepProps} />);

        fireEvent.click(screen.getByText("Back"));
        expect(mockFunctions.onBack).toHaveBeenCalledTimes(2);

        fireEvent.click(screen.getByText("Save Changes"));
        expect(mockFunctions.onFinalize).toHaveBeenCalledTimes(1);
    });

    it("disables buttons when disable prop is true", () => {
        const props = {
            ...baseProps,
            disable: true,
        };

        render(<SteppedModalFooter {...props} />);

        const buttons = screen.getAllByRole("button");
        buttons.forEach((button) => {
            expect(button).toBeDisabled();
        });
    });
});
