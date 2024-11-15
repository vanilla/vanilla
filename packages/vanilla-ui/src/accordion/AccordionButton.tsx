/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as Reach from "@reach/accordion";
import React from "react";

export const AccordionButton: React.ComponentType<{ children?: React.ReactNode; className?: string }> =
    Reach.AccordionButton;
