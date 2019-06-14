/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import { actions } from "@storybook/addon-actions";
import * as React from "react";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";

// This will lead to { onClick: action('onClick'), ... }
const eventsFromNames = actions("onClick", "onMouseOver");

// This will lead to { onClick: action('clicked'), ... }
const eventsFromObject = actions({ onClick: "clicked", onMouseOver: "hovered" });

storiesOf("Button", module)
    .add("default view", () => <Button {...eventsFromNames}>{t("Hello World!")}</Button>)
    .add("default view, different actions", () => <Button {...eventsFromObject}>{t("Hello World! 2")}</Button>);
