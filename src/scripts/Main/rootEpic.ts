import { combineEpics } from "redux-observable";

// bring in all epics from the various modules
import authenticateEpics from "@dashboard/Authenticate/state/epics";

const rootEpic = combineEpics(authenticateEpics);

export function getRootEpic() {
    return rootEpic;
}
