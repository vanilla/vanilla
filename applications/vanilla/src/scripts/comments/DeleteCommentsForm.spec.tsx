/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { mockAPI } from "@library/__tests__/utility";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import DeleteCommentsForm from "@vanilla/addon-vanilla/comments/DeleteCommentsForm";

const queryClient = new QueryClient();
describe("<DeleteCommentsForm />", () => {
    it("Should call the API on submission with the correct parameters and call it's callbacks", async () => {
        const closeFn = vi.fn();
        const onMutate = vi.fn();
        const mockApi = mockAPI();

        mockApi.onDelete("/comments/list").reply(204, {});

        const rendered = render(
            <QueryClientProvider client={queryClient}>
                <DeleteCommentsForm commentIDs={[4, 5]} close={closeFn} onMutateSuccess={onMutate} />
            </QueryClientProvider>,
        );

        // We should be able to set a type
        const fullCheck = await rendered.findByText("Full");

        await userEvent.click(fullCheck);

        const submitButton = await rendered.findByText("Delete");
        await userEvent.click(submitButton);

        expect(mockApi.history.delete.length).toBe(1);
        expect(JSON.parse(mockApi.history.delete[0].data)).toEqual({ commentIDs: [4, 5], deleteMethod: "full" });
        expect(closeFn).toHaveBeenCalledTimes(1);
        expect(onMutate).toHaveBeenCalledWith("full");
    });
});
