import { render, waitFor, screen } from "@testing-library/react";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { mockAPI } from "@library/__tests__/utility";
import { FAKE_INTEGRATIONS_CATALOG } from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import MockAdapter from "axios-mock-adapter";
import TriagePage from "@dashboard/moderation/TriagePage";
import { STORY_DISCUSSION } from "@library/storybook/storyData";

describe("TriagePage", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(/discussions.*/).reply(
            200,
            Array.from({ length: 3 }, (_, index) => ({
                ...STORY_DISCUSSION,
                discussionID: index + 1,
                name: `Example Triage Item ${index + 1}`,
                reports: {
                    ...CommunityManagementFixture.getReport({
                        reportID: index + 1,
                    }),
                },
            })),
        );
        mockAdapter.onGet("/attachments/catalog").reply(200, FAKE_INTEGRATIONS_CATALOG);
    });
    it("Renders triage list", async () => {
        render(CommunityManagementFixture.getWrappedComponent(<TriagePage />));
        await waitFor(() => {
            expect(screen.getByText("Example Triage Item 1")).toBeInTheDocument();
        });
        [1, 2, 3].forEach((index) => {
            expect(screen.getByText(`Example Triage Item ${index}`)).toBeInTheDocument();
        });
    });
});
