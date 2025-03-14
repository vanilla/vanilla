/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render } from "@testing-library/react";
import DashboardMeBox from "@library/headers/mebox/pieces/DashboardMeBox";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";

describe("DashboardMeBox", () => {
    it("Renders user name", async () => {
        const screen = render(
            <TestReduxProvider>
                <DashboardMeBox currentUser={UserFixture.adminAsCurrent.data} forceOpen />
            </TestReduxProvider>,
        );
        await vi.dynamicImportSettled();
        expect((await screen.findAllByText(/admin/)).length).toBeGreaterThanOrEqual(1);
    });
});
