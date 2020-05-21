/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import { PocketMultiRoleInput } from "../conditions/PocketMultiRoleInput";
import { PocketSubcommunityChooser } from "../conditions/PocketSubcommunitiesChooser";

addComponent("pocket-multi-role-input", PocketMultiRoleInput);

// Do something to prevent crash here.
addComponent("pocket-subcommunities-chooser", PocketSubcommunityChooser);
