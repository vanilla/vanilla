/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render } from "@testing-library/react";
import { deserializeHtml, VanillaEditorImpl } from "@library/vanilla-editor/VanillaEditor";
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
                    <VanillaEditorImpl legacyTextArea={form?.firstChild as HTMLInputElement} initialFormat={"rich2"} />
                </LiveAnnouncer>
            </TestReduxProvider>,
        );

        expect(queryByTestId("card-embed:undefined")).toBeNull();
    });

    it("Image embed from rich 1 converts to rich 2", async () => {
        const html = `<p>I was created with rich 1 and have an image</p><div class="embedExternal embedImage display-small float-none"><div class="embedExternal-content"><a class="embedImage-link" href="https://dev.vanilla.localhost/uploads/5M94RLFNRJ2S/61zxnknzvfl-ac-sl1000.jpg" rel="nofollow noopener ugc" target="_blank"><img class="embedImage-img" src="https://dev.vanilla.localhost/uploads/5M94RLFNRJ2S/61zxnknzvfl-ac-sl1000.jpg" alt="61zxnKNzvfL._AC_SL1000_.jpg" height="1000" width="1000" loading="lazy" data-display-size="small" data-float="none" data-type="image/jpeg" data-embed-type="image"></img></a></div></div><p><br></p>`;
        const nodes = [
            { type: "p", children: [{ text: "I was created with rich 1 and have an image" }] },
            {
                type: "rich_embed_card",
                dataSourceType: "image",
                url: "https://dev.vanilla.localhost/uploads/5M94RLFNRJ2S/61zxnknzvfl-ac-sl1000.jpg",
                children: [{ text: "" }],
                embedData: {
                    displaySize: "small",
                    embedType: "image",
                    float: "none",
                    name: "61zxnKNzvfL._AC_SL1000_.jpg",
                    type: "image/jpeg",
                    url: "https://dev.vanilla.localhost/uploads/5M94RLFNRJ2S/61zxnknzvfl-ac-sl1000.jpg",
                    height: 1000,
                    width: 1000,
                },
            },
            {
                type: "p",
                children: [{ text: "\n" }],
            },
        ];

        const deserialized = deserializeHtml(html);

        expect(deserialized).toStrictEqual(nodes);
    });
});
