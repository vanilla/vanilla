import React from "react";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import UserContent from "@library/content/UserContent";
import { setMeta } from "@library/utility/appUtils";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";

describe("UserContent", () => {
    it("Renders HTML content", () => {
        const MOCK_HTML = blessStringAsSanitizedHtml(`<h1>I am a header</h1><p>I am some text</p>`);
        render(<UserContent vanillaSanitizedHtml={MOCK_HTML} />);
        expect(screen.getByRole("heading", { level: 1 })).toBeInTheDocument();
        expect(screen.getByText("I am some text")).toBeInTheDocument();
    });
    it.skip("Renders mounts embedded content", async () => {
        const MOCK_HTML =
            blessStringAsSanitizedHtml(`<div class="js-embed embedResponsive" data-embedjson="{&quot;height&quot;:900,&quot;width&quot;:1600,&quot;url&quot;:&quot;https://codesandbox.io/embed/07-final-torus-knot-r3bo1?fontsize=14&amp;hidenavigation=1&amp;theme=dark&quot;,&quot;embedType&quot;:&quot;iframe&quot;,&quot;embedStyle&quot;:&quot;rich_embed_card&quot;}">
        <a href="https://codesandbox.io/embed/07-final-torus-knot-r3bo1?fontsize=14&amp;hidenavigation=1&amp;theme=dark" rel="nofollow noopener ugc">
            https://codesandbox.io/embed/07-final-torus-knot-r3bo1?fontsize=14&amp;hidenavigation=1&amp;theme=dark
        </a>
    </div>`);
        setMeta("trustedDomains", "https://codesandbox*");
        render(<UserContent vanillaSanitizedHtml={MOCK_HTML} />);
        await vi.dynamicImportSettled();
        const iframe = screen.getByTestId(`iframe-embed`);
        expect(iframe).toBeVisible();
    });
});
