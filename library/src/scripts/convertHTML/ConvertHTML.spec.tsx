/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ConvertHtmlRouteParams, ConvertHTMLImpl } from "@library/convertHTML/ConvertHTML";
import { render, screen, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider, useQueryClient } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { MemoryRouter } from "react-router";

const TEST_ID = "formatted-body";

const MOCK_HTML_INPUT = `<p>This is some sample HTML</p>`;
const MOCK_LI_VIDEO_INPUT = `<p>This is some sample HTML <li-video vid="6341258652112" width="960" height="540" size="original" uploading="false" thumbnail="https://image.png" align="center"/></p>`;

const mockAdapter = mockAPI();
mockAdapter.onGet("/discussions/1").reply(200, { body: MOCK_HTML_INPUT });
mockAdapter.onGet("/discussions/2").reply(200, { body: MOCK_LI_VIDEO_INPUT });

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

function MockWrapper(props: ConvertHtmlRouteParams) {
    return (
        <QueryClientProvider client={queryClient}>
            <MemoryRouter>
                <ConvertHTMLImpl {...props} />
            </MemoryRouter>
        </QueryClientProvider>
    );
}

describe("Convert HTML to Rich", () => {
    it("Converts simple html into rich 1 format", async () => {
        render(<MockWrapper format="rich" recordType="discussion" recordID="1" />);

        const expectedValue = [{ insert: "This is some sample HTML\n" }];

        const bodyEl = screen.getByTestId(TEST_ID);
        expect(bodyEl).toBeInTheDocument();

        await waitFor(() => {
            expect(bodyEl).toHaveTextContent(JSON.stringify(expectedValue));
        });
    });

    it("Converts simple html into rich2 format", async () => {
        render(<MockWrapper format="rich2" recordType="discussion" recordID="1" />);

        const expectedValue = [
            {
                type: "p",
                children: [{ text: "This is some sample HTML" }],
            },
        ];

        const bodyEl = screen.getByTestId(TEST_ID);
        expect(bodyEl).toBeInTheDocument();

        await waitFor(() => {
            expect(bodyEl).toHaveTextContent(JSON.stringify(expectedValue));
        });
    });

    it("Converts li-video into rich 1 format as code item", async () => {
        render(<MockWrapper format="rich" recordType="discussion" recordID="2" />);

        const expectedValue = [
            { insert: "This is some sample HTML " },
            {
                attributes: { code: true },
                insert: 'li-video vid="6341258652112"_BrightCoverVideo_To_Kaltura li-video',
            },
            { insert: "\n" },
        ];

        const bodyEl = screen.getByTestId(TEST_ID);
        expect(bodyEl).toBeInTheDocument();

        await waitFor(() => {
            expect(bodyEl).toHaveTextContent(JSON.stringify(expectedValue));
        });
    });

    it("Converts li-video into rich2 format as code item", async () => {
        render(<MockWrapper format="rich2" recordType="discussion" recordID="2" />);

        const expectedValue = [
            {
                type: "p",
                children: [
                    { text: "This is some sample HTML " },
                    {
                        text: 'li-video vid="6341258652112"_BrightCoverVideo_To_Kaltura li-video',
                        code: true,
                    },
                ],
            },
        ];

        const bodyEl = screen.getByTestId(TEST_ID);
        expect(bodyEl).toBeInTheDocument();

        await waitFor(() => {
            expect(bodyEl).toHaveTextContent(JSON.stringify(expectedValue));
        });
    });

    it("Display error if format is not a rich format", async () => {
        render(<MockWrapper format="markdown" recordType="discussion" recordID="1" />);
        const bodyEl = screen.getByTestId(TEST_ID);
        expect(bodyEl).toBeInTheDocument();

        await waitFor(() => {
            expect(bodyEl).toHaveTextContent(`ERROR: URL param for "format" must be one of ["rich", "rich2"]`);
        });
    });
});
