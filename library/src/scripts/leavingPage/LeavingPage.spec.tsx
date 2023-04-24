/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary */

import React from "react";
import { render, screen } from "@testing-library/react";
import { LeavingPageImpl } from "@library/leavingPage/LeavingPage";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { MemoryRouter } from "react-router";

describe("Leaving Page", () => {
    const SITE_NAME = "My Community";
    const EXTERNAL_URL = "https://exampleexternalsite.com";
    it("Should render the page with back to home, external link as text and button as link", async () => {
        render(
            <TestReduxProvider
                state={{
                    users: {
                        usersByID: {
                            2: {
                                status: LoadStatus.SUCCESS,
                                data: UserFixture.createMockUser(),
                            },
                        },
                    },
                }}
            >
                <MemoryRouter>
                    <LeavingPageImpl siteName={SITE_NAME} target={EXTERNAL_URL} />
                </MemoryRouter>
            </TestReduxProvider>,
        );

        expect(screen.getByText(`Back to ${SITE_NAME}`)).toBeInTheDocument();

        const linkAsTextInSpan = screen.getByTestId("external-link-as-text");
        expect(linkAsTextInSpan).toBeInTheDocument();
        expect(linkAsTextInSpan.innerHTML).toBe(`${EXTERNAL_URL}/`);

        const externalSiteLink = screen.getByRole("button", {
            name: "Continue to External Site",
        });
        expect(externalSiteLink).toBeInTheDocument();
        expect(externalSiteLink).toHaveAttribute("href");
        expect(externalSiteLink.getAttribute("href")).toBe(`${EXTERNAL_URL}/`);
    });

    it("We will have an error page if the url is not valid or its an internal link", async () => {
        render(
            <TestReduxProvider
                state={{
                    users: {
                        usersByID: {
                            2: {
                                status: LoadStatus.SUCCESS,
                                data: UserFixture.createMockUser(),
                            },
                        },
                    },
                }}
            >
                <MemoryRouter>
                    <LeavingPageImpl siteName={SITE_NAME} target={"invalidurl"} />
                </MemoryRouter>
            </TestReduxProvider>,
        );

        expect(
            await screen.queryByRole("button", {
                name: "Continue to External Site",
            }),
        ).not.toBeInTheDocument();

        const errorMessage = screen.getByRole("heading");
        expect(errorMessage).toBeInTheDocument();
        expect(errorMessage).toHaveTextContent("Url is invalid.");
    });
});
