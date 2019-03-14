/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import throttle from "lodash/throttle";

export enum Devices {
    MOBILE = "mobile",
    TABLET = "tablet",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
    DESKTOP = "desktop",
}

export interface IDeviceProps {
    device: Devices;
}

interface IDeviceCheckerProps {
    doUpdate: () => void;
}
