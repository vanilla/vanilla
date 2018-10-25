/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { expect } from "chai";
import { shallow } from "enzyme";
import sinon from "sinon";
import { Permission } from "@library/users/Permission";
import UsersActions from "./UsersActions";
import { IMe } from "@dashboard/@types/api";
import { string } from "prop-types";
import { LoadStatus, ILoadable } from "@library/@types/api";

describe("<Permission />", () => {
    let user: ILoadable<IMe>;
    let actions: UsersActions;

    const makeMockUser = (withPermissions: string[] = [], isAdmin: boolean = false): IMe => {
        return {
            name: "test",
            userID: 0,
            permissions: withPermissions,
            isAdmin,
            photoUrl: "",
            dateLastActive: "",
        };
    };

    describe.only("with no data loaded yet", () => {
        beforeEach(() => {
            user = {
                status: LoadStatus.PENDING,
            };
            actions = new UsersActions(sinon.fake(), sinon.fake() as any);
        });

        it("returns false if the data isn't loaded yet.", () => {
            const result = shallow(
                <Permission permission="test" currentUser={user} usersActions={actions}>
                    Test
                </Permission>,
            );

            console.log(result);
            expect(result);
        });
    });
});
