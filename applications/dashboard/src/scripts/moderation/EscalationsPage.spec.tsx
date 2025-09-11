/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { fireEvent, render, screen, waitFor } from "@testing-library/react";

import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import EscalationsPage from "@dashboard/moderation/EscalationsPage";
import { FAKE_INTEGRATIONS_CATALOG } from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import MockAdapter from "axios-mock-adapter";
import React from "react";
import { mockAPI } from "@library/__tests__/utility";

describe("EscalationsPage", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(/escalations.*/).reply(
            200,
            Array.from({ length: 3 }, (_, index) =>
                CommunityManagementFixture.getEscalation({
                    escalationID: index + 1,
                    recordName: `Escalation ${index + 1}`,
                }),
            ),
        );
        mockAdapter.onGet("/attachments/catalog").reply(200, FAKE_INTEGRATIONS_CATALOG);
    });
    it("Renders escalation list", async () => {
        render(CommunityManagementFixture.getWrappedComponent(<EscalationsPage />));
        await waitFor(() => {
            expect(screen.getByText("Escalation 1")).toBeInTheDocument();
        });
        [1, 2, 3].forEach((index) => {
            expect(screen.getByText(`Escalation ${index}`)).toBeInTheDocument();
        });
    });
});
