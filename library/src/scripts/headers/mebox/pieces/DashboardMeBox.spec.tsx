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
    it("Renders user name", () => {
        const { findByText } = render(
            <TestReduxProvider>
                <DashboardMeBox currentUser={UserFixture.adminAsCurrent} forceOpen />
            </TestReduxProvider>,
        );
        expect(findByText(/admin/)).toBeTruthy();
    });
});
