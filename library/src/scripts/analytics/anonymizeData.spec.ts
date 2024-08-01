/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    getAnonymizeData,
    setAnonymizeData,
    META_KEY_ANONYMIZE,
    AnonymizeOptions,
    getCookieData,
} from "@library/analytics/anonymizeData";
import { setMeta } from "@library/utility/appUtils";
import { mockAPI } from "@library/__tests__/utility";
import getStore from "@library/redux/getStore";
import apiv2 from "@library/apiv2";
import UserActions from "@library/features/users/UserActions";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import MockAdapter from "axios-mock-adapter/types";

const API_URL = "/analytics/privacy";

describe("Get and set AnonymizeData preference for user that is not logged in.", () => {
    let mockAdapter: MockAdapter;
    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter
            .onGet("/config?select=garden.cookie.name,garden.cookie.path")
            .reply(200, { "garden.cookie.name": "TestCookies", "garden.cookie.path": "/" });
    });

    afterEach(() => {
        document.cookie = "";
        mockAdapter.reset();
    });

    it("User does not have a preference set in the cookies and site's preference is not set, return false", async () => {
        const result = await getAnonymizeData();
        expect(result).toBeFalsy();
    });

    it("User does not have a preference set in the cookies, return the site's default of true", async () => {
        setAnonymizeMeta(true);
        const result = await getAnonymizeData();
        expect(result).toBeTruthy();
    });

    it("User has AnonymizeData set to true in the cookies, return true although site setting is false", async () => {
        const cookie = await anonymizeCookie(true);
        document.cookie = cookie;
        const result = await getAnonymizeData();
        expect(result).toBeTruthy();
    });

    it("User has AnonymizeData set to false in the cookies, return false although site setting true", async () => {
        setAnonymizeMeta(true);
        const cookie = await anonymizeCookie(false);
        document.cookie = cookie;
        const result = await getAnonymizeData();
        expect(result).toBeFalsy();
    });

    it("User selected to anonymize data, add to cookie and return true although site setting is false", async () => {
        setAnonymizeMeta(false);
        const result = await setAnonymizeData(true);
        expect(result).toBeTruthy();
        const cookie = await anonymizeCookie(true);
        expect(document.cookie).toBe(cookie);
    });

    it("User selected to not anonymize data, add to cookie and return false although site setting is true", async () => {
        setAnonymizeMeta(true);
        const result = await setAnonymizeData(false);
        expect(result).toBeFalsy();
        const cookie = await anonymizeCookie(false);
        expect(document.cookie).toBe(cookie);
    });

    it("User selected to use default site setting of true", async () => {
        setAnonymizeMeta(true);
        const result = await setAnonymizeData();
        expect(result).toBeTruthy();
    });
});

describe("Get and set AnonymizeData preference for use that is logged in.", () => {
    const store = getStore();
    const userActions = new UserActions(store.dispatch, apiv2);
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/users/me").reply(200, UserFixture.createMockUser());
        mockAdapter.onGet("/users/$me/permissions?expand=junctions").reply(200, {
            isAdmin: true,
            isSysAdmin: false,
            permissions: [UserFixture.globalAdminPermissions],
            junctions: {
                knowledgeBase: [],
                category: [],
            },
            junctionAliases: {
                category: [],
            },
        });
        userActions.getMe();
        userActions.getPermissions();
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("Vanilla Analytics is disabled and endpoint is not reachable, return site default when getting value", async () => {
        mockAdapter.onGet(/config/).reply(200, {});
        setAnonymizeMeta(false);
        mockAdapter.onGet(API_URL).networkError();
        const result = await getAnonymizeData();
        expect(result).toBeFalsy();
    });

    it("User does not have anonymize data set in the database. Use the site default setting", async () => {
        mockAdapter.onGet(API_URL).reply(200, { AnonymizeData: AnonymizeOptions.DEFAULT });
        setAnonymizeMeta(true);
        const result = await getAnonymizeData();
        expect(result).toBeTruthy();
    });

    it("User has saved preference to anonymize data. Return true despite site default being false.", async () => {
        mockAdapter.onGet(API_URL).reply(200, { AnonymizeData: AnonymizeOptions.TRUE });
        setAnonymizeMeta(false);
        const result = await getAnonymizeData();
        expect(result).toBeTruthy();
    });

    it("User has saved preference to not anonymize data. Return false despite site setting of true.", async () => {
        mockAdapter.onGet(API_URL).reply(200, { AnonymizeData: AnonymizeOptions.FALSE });
        setAnonymizeMeta(true);
        const result = await getAnonymizeData();
        expect(result).toBeFalsy();
    });

    it("Vanilla Analytics is disabled and endpoint is not reachable, return site default when setting value", async () => {
        mockAdapter.onPost(API_URL).networkError();
        setAnonymizeMeta(true);
        const result = await setAnonymizeData(false);
        expect(result).toBeTruthy();
    });

    it("Set user preference to anonymize data. Return true despite site setting being false", async () => {
        mockAdapter.onPost(API_URL).reply(201, { AnonymizeData: AnonymizeOptions.TRUE });
        setAnonymizeMeta(false);
        const result = await setAnonymizeData(true);
        expect(result).toBeTruthy();
    });

    it("Set user preference to use site default. Return false.", async () => {
        mockAdapter.onPost(API_URL).reply(201, { AnonymizeData: AnonymizeOptions.DEFAULT });
        setAnonymizeMeta(false);
        const result = await setAnonymizeData();
        expect(result).toBeFalsy();
    });
});

function setAnonymizeMeta(value: boolean): void {
    setMeta(META_KEY_ANONYMIZE, value);
}

async function anonymizeCookie(value: boolean): Promise<string> {
    const cookieData = await getCookieData();
    const cookieName = cookieData["garden.cookie.name"] + "-" + META_KEY_ANONYMIZE;
    return [cookieName, value].join("=");
}
