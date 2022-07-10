/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

export const SectionBehaviourContext = React.createContext({
    autoWrap: false,
    isSticky: true,
    useMinHeight: true,
});
