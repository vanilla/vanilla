/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { expect } from "chai";
import mentionReducer, { initialState, IMentionState } from "./mentionReducer";
import * as mentionActions from "./mentionActions";
import sinon, { SinonSandbox } from "sinon";
import api from "@dashboard/apiv2";
import { IMentionNode } from "./MentionTrie";

describe("mentionReducer", () => {
    const sandbox: SinonSandbox = sinon.createSandbox();
    afterEach(() => sandbox.restore());

    it("should return the initial state", () => {
        expect(mentionReducer(undefined, {} as any)).deep.equals(initialState);
    });

    it("can handle LOAD_USERS_REQUEST", () => {
        const response = "";
        sandbox.stub(api, "get");

        const action = mentionActions.actions.loadUsersRequest("test");

        expect(mentionReducer(undefined, action).users.getValue("test")).to.deep.eq({ status: "PENDING" });
    });

    // it("can ")
});
