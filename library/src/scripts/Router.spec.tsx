/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Router } from "@library/Router";
import { createMemoryHistory, History } from "history";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { render } from "@testing-library/react";
import { initPageViewTracking } from "@library/pageViews/pageViewTracking";
import { Route } from "react-router";
import { act } from "react-dom/test-utils";
import { RouterRegistry } from "@library/Router.registry";
import { vitest } from "vitest";

// Mock so we don't crash when calling it.
(global as any).scrollTo = vitest.fn();

describe("<Router />", () => {
    let history: History;

    beforeAll(() => {
        history = createMemoryHistory();
        initPageViewTracking(history);
        RouterRegistry.addRoutes([<Route key={"my-route"} path={"/test-path"} component={TestRouteContents} />]);
    });

    beforeEach(() => {
        history.push("/");
    });

    it("Displays a not found page if the route doesn't match.", async () => {
        const rendered = render(
            <TestReduxProvider>
                <Router history={history} />
            </TestReduxProvider>,
        );

        const title = rendered.getByRole("heading");
        expect(title).toHaveTextContent("Page Not Found");
    });

    it("Can render registered routes", async () => {
        history.push("/test-path");
        const rendered = render(
            <TestReduxProvider>
                <Router history={history} />
            </TestReduxProvider>,
        );

        const title = rendered.getByRole("heading");
        expect(title).toHaveTextContent("Hello Route");
    });

    it("Displays server side errors", async () => {
        function Content() {
            return (
                <TestReduxProvider
                    state={{
                        route: {
                            error: {
                                message: "An error occured",
                                description: "OMG what happened!",
                                status: 500,
                            },
                        },
                    }}
                >
                    <Router history={history} />
                </TestReduxProvider>
            );
        }
        const rendered = render(<Content />);

        const title = await rendered.findByRole("heading");
        expect(title).toHaveTextContent("An error occured");
        expect(title.nextElementSibling).toHaveTextContent("OMG what happened!");

        // Server side errors are cleared when navigating away.
        act(() => {
            history.push("/test-path");
        });

        const routeTitle = await rendered.findByTestId("routetitle");
        expect(routeTitle).toHaveTextContent("Hello Route");
    });
});

function TestRouteContents() {
    return <h1 data-testid="routetitle">Hello Route</h1>;
}
