/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor } from "@testing-library/react";
import { AnalyticsData, onPageViewWithContext } from "@library/analytics/AnalyticsData";

describe("AnalyticsData", () => {
    it("AnalyticsData component fires a dispatch event", () => {
        const dispatchEventSpy = jest.spyOn(document, "dispatchEvent");
        render(<AnalyticsData uniqueKey={"test"} />);
        expect(dispatchEventSpy).toHaveBeenCalled();
    });
    it("onPageViewWithContext fires callback when event is dispatched", () => {
        const mockCallback = jest.fn();
        onPageViewWithContext(mockCallback);
        document.dispatchEvent(new CustomEvent("pageViewWithContext", { detail: "test" }));
        expect(mockCallback).toHaveBeenCalled();
    });
});
