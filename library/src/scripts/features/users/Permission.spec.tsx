/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { render, fireEvent, waitFor, screen } from "@testing-library/react";
import "@testing-library/jest-dom/extend-expect";
import React, { ReactElement } from "react";

jest.mock("@library/apiv2");

const renderWithClear: typeof render = (ui: ReactElement): any => {
    document.body.innerHTML = "";
    return render(ui);
};

function assertHasPermission() {
    expect(screen.queryByText("Success")).toBeInTheDocument();
}

function assertNoPermission() {
    expect(screen.queryByText("Success")).not.toBeInTheDocument();
}

function assertHasFallback() {
    expect(screen.queryByText("Fallback")).toBeInTheDocument();
}

describe("<Permission />", () => {
    const fallbackComponent = <div data-testid="fallback">{`Fallback`}</div>;
    const successComponent = <div>{`Success`}</div>;
    describe("with no data loaded yet", () => {
        it("returns nothing if the data isn't loaded yet.", async () => {
            renderWithClear(
                <TestReduxProvider
                    state={{
                        users: {
                            permissions: {
                                status: LoadStatus.PENDING,
                            },
                        },
                    }}
                >
                    <Permission permission="test">
                        <span data-testid="perm">Test</span>
                    </Permission>
                </TestReduxProvider>,
            );

            assertNoPermission();
        });

        it("loads the fallback if nothing is rendered yet.", () => {
            renderWithClear(
                <TestReduxProvider
                    state={{
                        users: {
                            permissions: {
                                status: LoadStatus.PENDING,
                            },
                        },
                    }}
                >
                    <Permission permission="test" fallback={fallbackComponent}>
                        {successComponent}
                    </Permission>
                </TestReduxProvider>,
            );

            assertNoPermission();
            assertHasFallback();
        });
    });

    describe("with data", () => {
        function Wrapper(props: any) {
            return (
                <TestReduxProvider
                    state={{
                        users: {
                            permissions: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    isAdmin: props.isAdmin ?? false,
                                    permissions: [
                                        {
                                            type: "global",
                                            id: null,
                                            permissions: {
                                                perm1: true,
                                                perm2: true,
                                                perm3: true,
                                                "someResource.globalOnly": true,
                                                "someResource.view": true,
                                            },
                                        },
                                        {
                                            type: "someResource",
                                            id: 5,
                                            permissions: {
                                                "someResource.view": true,
                                                "someResource.add": true,
                                            },
                                        },
                                    ],
                                    junctions: {
                                        someResource: [4, 5, 20],
                                    },
                                    junctionAliases: {
                                        someResource: {
                                            "50": 5,
                                        },
                                    },
                                },
                            },
                        },
                    }}
                >
                    {props.children}
                </TestReduxProvider>
            );
        }

        it("renders children if the user has one of the given permissions", () => {
            renderWithClear(
                <Wrapper>
                    <Permission permission="perm1">{successComponent}</Permission>
                </Wrapper>,
            );

            assertHasPermission();

            // 1 good + 1 bad = success
            renderWithClear(
                <Wrapper>
                    <Permission permission={["perm1", "otherPerm"]}>{successComponent}</Permission>
                </Wrapper>,
            );
            assertHasPermission();
        });

        it("renders children if the user does not have one of the given permissions", () => {
            // 1 bad = failed
            let result = renderWithClear(
                <Wrapper>
                    <Permission permission="bad">{successComponent}</Permission>
                </Wrapper>,
            );

            assertNoPermission();

            // 2 bad = failed
            result = renderWithClear(
                <Wrapper>
                    <Permission permission={["bad1", "bad2"]}>{successComponent}</Permission>
                </Wrapper>,
            );
            assertNoPermission();
        });

        describe("checks resource specific permission", () => {
            it("checks resource specific permission", () => {
                renderWithClear(
                    <Wrapper>
                        <Permission resourceID={5} resourceType={"someResource"} permission="someResource.view">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertHasPermission();
            });

            it("doesn't use resource permissions with global only permissions", () => {
                renderWithClear(
                    <Wrapper>
                        <Permission resourceID={5} resourceType={"someResource"} permission="someResource.globalOnly">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertNoPermission();
            });

            it(`Mode - ${PermissionMode.GLOBAL_OR_RESOURCE}`, () => {
                renderWithClear(
                    <Wrapper>
                        <Permission mode={PermissionMode.GLOBAL_OR_RESOURCE} permission="someResource.add">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertHasPermission();
            });

            it(`Mode - GLOBAL - ignore resource permission`, () => {
                renderWithClear(
                    <Wrapper>
                        <Permission mode={PermissionMode.GLOBAL} permission="someResource.add">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertNoPermission();
            });

            it(`Mode - RESOURCE_IF_JUNCTION - Fallback to global if no resource junctions`, () => {
                renderWithClear(
                    <Wrapper>
                        <Permission resourceType={"someResource"} resourceID={10000} permission="someResource.view">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertHasPermission();
            });

            it(`Mode - RESOURCE_IF_JUNCTION - Enforce junction resource permissions`, () => {
                renderWithClear(
                    <Wrapper>
                        <Permission resourceType={"someResource"} resourceID={4} permission="someResource.add">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertNoPermission();
            });

            it(`Mode - RESOURCE_IF_JUNCTION - Resolve aliases`, () => {
                renderWithClear(
                    <Wrapper>
                        <Permission resourceType={"someResource"} resourceID={50} permission="someResource.add">
                            {successComponent}
                        </Permission>
                    </Wrapper>,
                );

                assertHasPermission();
            });
        });

        it("renders children if the user has 'isAdmin' set", () => {
            renderWithClear(
                <Wrapper isAdmin={true}>
                    <Permission permission="asd">{successComponent}</Permission>
                </Wrapper>,
            );
            assertHasPermission();
        });
    });
});
