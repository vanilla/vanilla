/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { render, screen } from "@testing-library/react";
import { newPostItems } from "@library/newPostMenu/NewPostMenu.fixture";
import NewPostMenu from "@library/newPostMenu/NewPostMenu";

describe("NewPostMenu", () => {
    it("Rendered in panel area as a last widget, panel height should adjust to contain the dropdown without scroller", async () => {
        render(
            <div className="panelArea" data-testid="panelArea" style={{ height: 60 }}>
                <NewPostMenu items={newPostItems} titleType={"none"} forceDesktopOnly />
            </div>,
        );
        const container = await screen.findByTestId("panelArea");

        expect(container.style.minHeight).toBe("240px");
    });

    it("Not the last widget, but the last widget's height is not enough for dropdown, panel height still should adjust to contain the dropdown without scroller", async () => {
        render(
            <div className="panelArea" data-testid="panelArea" style={{ height: 120 }}>
                <NewPostMenu items={newPostItems} titleType={"none"} forceDesktopOnly />
                <div style={{ height: 80 }}>Some widget</div>
            </div>,
        );
        const container = await screen.findByTestId("panelArea");

        expect(container.style.minHeight).toBe("220px");
    });
});
