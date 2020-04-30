/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { assert } from "chai";
import { mount, shallow } from "enzyme";
import React from "react";

// tslint:disable:jsx-use-translation-function

describe("<Permission />", () => {
    describe("with no data loaded yet", () => {
        it("returns nothing if the data isn't loaded yet.", () => {
            const result = shallow(
                <TestReduxProvider
                    state={{
                        users: {
                            permissions: {
                                status: LoadStatus.PENDING,
                            },
                        },
                    }}
                >
                    <Permission permission="test">Test</Permission>
                </TestReduxProvider>,
            );

            assert(!result.contains("test"), "Something was rendered");
        });

        it("loads the fallback if nothing is rendered yet.", () => {
            const fallback = <div>{`fallback`}</div>;
            const result = mount(
                <TestReduxProvider
                    state={{
                        users: {
                            permissions: {
                                status: LoadStatus.PENDING,
                            },
                        },
                    }}
                >
                    <Permission permission="test" fallback={fallback}>
                        Test
                    </Permission>
                </TestReduxProvider>,
            );

            assert(result.contains("fallback"), "The fallback was not equivalent to the reuslt");
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
            const successComponent = <div>{`Success`}</div>;
            let result = mount(
                <Wrapper>
                    <Permission permission="perm1">{successComponent}</Permission>
                </Wrapper>,
            );

            assert(result.contains("Success"), "the success component did not render with 1 good permission passed");

            result = mount(
                <Wrapper>
                    <Permission permission={["perm1", "asdfa"]}>{successComponent}</Permission>
                </Wrapper>,
            );

            assert(
                result.contains("Success"),
                "the success component did not render with 1 good and 1 bad permission passed",
            );
        });

        it("renders children if the user does not have one of the given permissions", () => {
            const successComponent = <div>{`Success`}</div>;
            let result = mount(
                <Wrapper>
                    <Permission permission="asd">{successComponent}</Permission>
                </Wrapper>,
            );

            assert(!result.contains("Success"), "the success component rendered with 1 bad permission passed");

            result = mount(
                <Wrapper>
                    <Permission permission={["fds", "asdfa"]}>{successComponent}</Permission>
                </Wrapper>,
            );

            assert(!result.contains("Success"), "the success component did not render with 2 bad permissions passed");
        });

        it("checks resource specific permission", () => {
            const successComponent = <div>{`Success`}</div>;
            let result = mount(
                <Wrapper>
                    <Permission resourceID={5} resourceType={"someResource"} permission="someResource.view">
                        {successComponent}
                    </Permission>
                </Wrapper>,
            );

            assert(result.contains("Success"), "the resource permission is checked");

            result = mount(
                <Wrapper>
                    <Permission resourceID={5} resourceType={"someResource"} permission="someResource.globalOnly">
                        {successComponent}
                    </Permission>
                </Wrapper>,
            );

            assert(!result.contains("Success"), "globalOnly permission should not appear when checking a resource");

            result = mount(
                <Wrapper>
                    <Permission resourceID={5} resourceType={"someResource"} permission="someResource.add">
                        {successComponent}
                    </Permission>
                </Wrapper>,
            );

            assert(result.contains("Success"), "A resource only permission must work");

            result = mount(
                <Wrapper>
                    <Permission mode={PermissionMode.GLOBAL_OR_RESOURCE} permission="someResource.add">
                        {successComponent}
                    </Permission>
                </Wrapper>,
            );

            assert(result.contains("Success"), "Resource permission must work in global or resource mode");

            result = mount(
                <Wrapper>
                    <Permission mode={PermissionMode.GLOBAL} permission="someResource.add">
                        {successComponent}
                    </Permission>
                </Wrapper>,
            );

            assert(!result.contains("Success"), "Resource permission must be ignored in in global mode");
        });

        it("renders children if the user has 'isAdmin' set", () => {
            const successComponent = <div>{`Success`}</div>;
            const result = shallow(
                <Wrapper isAdmin={true}>
                    <Permission permission="asd">{successComponent}</Permission>
                </Wrapper>,
            );

            assert(
                result.contains("Success"),
                "the success component did not render with 1 bad permission passed and an isAdmin flag set to true",
            );
        });
    });
});
