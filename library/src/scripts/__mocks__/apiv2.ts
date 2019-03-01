/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import MockAdapter from "axios-mock-adapter";

const mock = jest.genMockFromModule("@library/apiv2") as any;
const adapter = new MockAdapter(mock.default);

export function __mockApi() {
    return adapter;
}

export default adapter;
