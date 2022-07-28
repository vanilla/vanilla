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
import { LoadStatus } from "@library/@types/api/core";

const ProviderWrapper = ({ children }) => {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    permissions: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            isAdmin: true,
                            isSysAdmin: false,
                            permissions: [UserFixture.globalAdminPermissions],
                        },
                    },
                },
            }}
        >
            {children}
        </TestReduxProvider>
    );
};

describe("DashboardMeBox", () => {
    it("Renders user name", () => {
        const { findByText } = render(
            <ProviderWrapper>
                <DashboardMeBox currentUser={UserFixture.adminAsCurrent} forceOpen />
            </ProviderWrapper>,
        );
        expect(findByText(/admin/)).toBeTruthy();
    });
});
