/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render } from "@testing-library/react";
import { ContentItemWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";

describe("<ContentItemWarning />", () => {
    const mockModalData = {
        warning: {
            dateInserted: "2021-10-01",
            format: "rich2",
            body: "<p>Test content here</p>",
            user: {
                userID: 1,
                name: "Test User",
                photoUrl: "https://test.com",
                dateLastActive: "2021-10-01",
            },
            moderatorNote: "Only for moderators",
            warningType: {
                name: "Test Warning",
                description: "Test Warning Description",
                warningTypeID: 1,
            },
            userNoteID: 2,
            conversationID: 3,
        },
        recordName: "Test Post",
        recordUrl: "https://test.com",
    };
    it("All the fields are present", () => {
        const result = render(
            <ContentItemWarning
                warning={mockModalData.warning}
                recordName={mockModalData.recordName}
                recordUrl={mockModalData.recordUrl}
                forceModalVisibility
                moderatorNoteVisible
            />,
        );
        expect(result.getByText(mockModalData.recordName)).toBeInTheDocument();
        expect(result.getByText(/Test Warning/)).toBeInTheDocument();
        expect(result.getByText(/Test Warning Description/)).toBeInTheDocument();
        expect(result.getByText(mockModalData.warning.moderatorNote)).toBeInTheDocument();
        expect(result.getByText("Test content here")).toBeInTheDocument();
        expect(result.getByRole("link", { name: "View Message" })).toBeInTheDocument();
        expect(result.getByRole("link", { name: "View Message" })).toHaveAttribute("href");
        expect(
            result
                .getByRole("link", { name: "View Message" })
                .getAttribute("href")
                ?.includes(`/messages/${mockModalData.warning.conversationID}#latest`),
        ).toBeTruthy();
    });
});
