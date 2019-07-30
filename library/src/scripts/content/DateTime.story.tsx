/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { select, date } from "@storybook/addon-knobs";
import DateTime from "@library/content/DateTime";

storiesOf("CoreComponents/DateTime", module).add("DateTime", () => {
    const mode = select(
        "mode",
        {
            fixed: "fixed",
            relative: "relative",
        },
        "fixed",
    );

    const day = date("timestamp", new Date("Jan 20 2017"));
    return (
        <>
            <DateTime timestamp={new Date(day).toISOString()} mode={mode as "relative"} />
            <br />
        </>
    );
});
