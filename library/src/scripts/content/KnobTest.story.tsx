/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { withKnobs, text, boolean, number } from "@storybook/addon-knobs";

const stories = storiesOf("Storybook Knobs", module);

// Knobs for React props
stories.add("with a button", () => (
    <button disabled={boolean("Disabled", false)}>{text("Label", "Hello Storybook")}</button>
));

// Knobs as dynamic variables.
stories.add("as dynamic variables", () => {
    const name = text("Name", "Arunoda Susiripala");
    const age = number("Age", 89);

    const content = `I am ${name} and I'm ${age} years old.`;
    return <div>{content}</div>;
});
