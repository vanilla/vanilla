/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { combineEpics } from "redux-observable";

// bring in all epics from the various modules
import authenticateEpics from "./epics/authenticateEpics";

const rootEpic = combineEpics(authenticateEpics);

export function getRootEpic() {
    return rootEpic;
}
