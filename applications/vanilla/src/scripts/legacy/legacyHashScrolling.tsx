/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useHashScrolling } from "@library/content/hashScrolling";
import React from "react";
import { mountPortal } from "@vanilla/react-utils";
import { MODAL_CONTAINER_ID } from "@library/modal/mountModal";

export function triggerLegacyHashScrolling() {
    mountPortal(<LegacyHashScroller />, MODAL_CONTAINER_ID);
}

function LegacyHashScroller() {
    useHashScrolling("");

    return <React.Fragment />;
}
