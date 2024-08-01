/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { addComponent } from "@library/utility/componentRegistry";
import { PocketMultiRoleInput } from "../conditions/PocketMultiRoleInput";
import { PocketCategoryInput } from "../conditions/PocketCategoryInput";
import { PocketContentForm } from "../conditions/PocketContentForm";

addComponent("pocket-multi-role-input", PocketMultiRoleInput);

// Do something to prevent crash here.
addComponent("pocket-category-input", PocketCategoryInput);
addComponent("PocketContentForm", PocketContentForm, { bypassPortalManager: true, unmountBeforeRender: true });
