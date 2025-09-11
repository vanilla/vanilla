/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { STORY_ME_ADMIN } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render } from "@testing-library/react";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import type { ReactNode } from "react";

export class CommentSpecFixture {
    public static wrapInProvider = async (children: ReactNode, enableNetworkRequests?: boolean) => {
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    enabled: enableNetworkRequests ?? false,
                    retry: false,
                },
            },
        });

        render(
            <TestReduxProvider>
                <CurrentUserContextProvider
                    currentUser={{ ...STORY_ME_ADMIN, ...CommentFixture.mockComment.insertUser }}
                >
                    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
                </CurrentUserContextProvider>
            </TestReduxProvider>,
        );
        await vi.dynamicImportSettled();
    };
}
