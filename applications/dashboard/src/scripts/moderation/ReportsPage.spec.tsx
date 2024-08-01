/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, waitFor, screen } from "@testing-library/react";
import ReportsPage from "@dashboard/moderation/ReportsPage";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { FAKE_INTEGRATIONS_CATALOG } from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";

describe("ReportsPage", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(/reports.*/).reply(
            200,
            Array.from({ length: 3 }, (_, index) =>
                CommunityManagementFixture.getReport({
                    reportID: index + 1,
                    recordName: `Example Discussion Name ${index + 1}`,
                }),
            ),
        );
        mockAdapter.onGet("/attachments/catalog").reply(200, FAKE_INTEGRATIONS_CATALOG);
    });
    it("Renders report list", async () => {
        render(CommunityManagementFixture.getWrappedComponent(<ReportsPage />));
        await waitFor(() => {
            expect(screen.getByText("Example Discussion Name 1")).toBeInTheDocument();
        });
        [1, 2, 3].forEach((index) => {
            expect(screen.getByText(`Example Discussion Name ${index}`)).toBeInTheDocument();
        });
    });
});
