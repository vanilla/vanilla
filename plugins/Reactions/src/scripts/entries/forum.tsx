/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerReducer } from "@library/redux/reducerRegistry";
import { addComponent } from "@library/utility/componentRegistry";
import { ReactionListModule } from "@Reactions/modules/ReactionListModule";
import { reactionsSlice } from "@Reactions/state/ReactionsReducer";

registerReducer(reactionsSlice.name, reactionsSlice.reducer);
addComponent("ReactionListModule", ReactionListModule, { overwrite: true });
