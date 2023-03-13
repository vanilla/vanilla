/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";

describe("Vanilla Editor Rich Embed", () => {
    let form: HTMLFormElement | undefined;

    beforeAll(() => {
        const textarea = document.createElement("textarea");
        textarea.value = `[{"type":"rich_embed_card","children":[{"text":""}],"dataSourceType":"image","uploadFile":{},"error":{}},{"type":"p","children":[{"text":"lalalalala"}]}]`;
        form = document.createElement("form");
        form.appendChild(textarea);
    });

    it("Empty error is converted to paragraph", async () => {
        render(<VanillaEditor legacyTextArea={form?.firstChild as HTMLInputElement} initialFormat={"rich2"} />);

        waitFor(() => {
            expect(screen.queryByTestId("card-embed:undefined")).toBeNull();
        });
    });
});
