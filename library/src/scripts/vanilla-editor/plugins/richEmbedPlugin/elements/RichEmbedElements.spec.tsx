/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render } from "@testing-library/react";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { LiveAnnouncer } from "react-aria-live";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";

describe("Vanilla Editor Rich Embed", () => {
    let form: HTMLFormElement | undefined;

    beforeAll(() => {
        const textarea = document.createElement("textarea");
        textarea.value = `[{"type":"rich_embed_card","children":[{"text":""}],"dataSourceType":"image","uploadFile":{},"error":{}},{"type":"p","children":[{"text":"lalalalala"}]}]`;
        form = document.createElement("form");
        form.appendChild(textarea);
    });

    const mockAdapter = mockAPI();
    mockAdapter.onGet("/users/by-names").reply(200, []);

    afterAll(() => {
        mockAdapter.reset();
    });

    it("Empty error is converted to paragraph", async () => {
        const { queryByTestId } = render(
            <TestReduxProvider>
                <LiveAnnouncer>
                    <VanillaEditor legacyTextArea={form?.firstChild as HTMLInputElement} initialFormat={"rich2"} />
                </LiveAnnouncer>
            </TestReduxProvider>,
        );

        expect(queryByTestId("card-embed:undefined")).toBeNull();
    });
});
