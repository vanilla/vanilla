/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as Reach from "@reach/accordion";
import React from "react";
import * as Polymorphic from "../polymorphic";

export const AccordionButton: Polymorphic.ForwardRefComponent<
    "button",
    { children?: React.ReactNode; className?: string }
> = Reach.AccordionButton as any;
