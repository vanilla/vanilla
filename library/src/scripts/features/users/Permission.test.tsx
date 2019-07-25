/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Permission } from "@library/features/users/Permission";
import { mount, shallow } from "enzyme";
import UserActions from "@library/features/users/UserActions";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import sinon from "sinon";
import { assert } from "chai";

// tslint:disable:jsx-use-translation-function

const noop = () => {
    return;
};

describe("<Permission />", () => {
    let user: ILoadable<IMe>;
    let actions: UserActions;

    const makeMockUser = (withPermissions: string[] = [], isAdmin: boolean = false): IMe => {
        return {
            name: "test",
            userID: 0,
            permissions: withPermissions,
            isAdmin,
            photoUrl: "",
            dateLastActive: "",
            countUnreadNotifications: 1,
        };
    };

    describe("with no data loaded yet", () => {
        beforeEach(() => {
            user = {
                status: LoadStatus.PENDING,
            };
        });

        it("returns nothing if the data isn't loaded yet.", () => {
            const result = shallow(
                <Permission permission="test" currentUser={user} requestData={noop}>
                    Test
                </Permission>,
            );

            assert(result.isEmptyRender(), "Something was rendered");
        });

        it("loads the fallback if nothing is rendered yet.", () => {
            const fallback = <div>{`fallback`}</div>;
            const result = shallow(
                <Permission permission="test" currentUser={user} requestData={noop} fallback={fallback}>
                    Test
                </Permission>,
            );

            assert(result.equals(fallback), "The fallback was not equivalent to the reuslt");
        });

        it("dispatches an action to get the data", () => {
            const spy = sinon.spy();
            assert(!spy.called, "The spy was called before the component even mounted.");
            mount(
                <Permission permission="test" currentUser={user} requestData={spy}>
                    Test
                </Permission>,
            );

            assert(spy.called, "The spy was not called by the component.");
        });
    });

    describe("with data", () => {
        beforeEach(() => {
            user = {
                status: LoadStatus.SUCCESS,
                data: makeMockUser(["perm1", "perm2", "perm3"]),
            };
            actions = new UserActions(sinon.fake(), sinon.fake() as any);
        });

        it("renders children if the user has one of the given permissions", () => {
            const successComponent = <div>{`Success`}</div>;
            let result = shallow(
                <Permission permission="perm1" currentUser={user} requestData={noop}>
                    {successComponent}
                </Permission>,
            );

            assert(
                result.contains(successComponent),
                "the success component did not render with 1 good permission passed",
            );

            result = shallow(
                <Permission permission={["perm1", "asdfa"]} currentUser={user} requestData={noop}>
                    {successComponent}
                </Permission>,
            );

            assert(
                result.contains(successComponent),
                "the success component did not render with 1 good and 1 bad permission passed",
            );
        });

        it("renders children if the user does not have one of the given permissions", () => {
            const successComponent = <div>{`Success`}</div>;
            let result = shallow(
                <Permission permission="asd" currentUser={user} requestData={noop}>
                    {successComponent}
                </Permission>,
            );

            assert(!result.contains(successComponent), "the success component rendered with 1 bad permission passed");

            result = shallow(
                <Permission permission={["fds", "asdfa"]} currentUser={user} requestData={noop}>
                    {successComponent}
                </Permission>,
            );

            assert(
                !result.contains(successComponent),
                "the success component did not render with 2 bad permissions passed",
            );
        });

        it("renders children if the user has 'isAdmin' set", () => {
            const successComponent = <div>{`Success`}</div>;
            user = {
                status: LoadStatus.SUCCESS,
                data: makeMockUser([], true),
            };
            const result = shallow(
                <Permission permission="asd" currentUser={user} requestData={noop}>
                    {successComponent}
                </Permission>,
            );

            assert(
                result.contains(successComponent),
                "the success component did not render with 1 bad permission passed and an isAdmin flag set to true",
            );
        });
    });
});
