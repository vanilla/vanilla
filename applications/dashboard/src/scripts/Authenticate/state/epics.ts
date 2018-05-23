import { combineEpics } from "redux-observable";
import apiv2 from "@core/apiv2";
import { log, logError } from "@core/utility";
import "rxjs/add/operator/mergeMap";
import "rxjs/add/operator/throttleTime";
import { AUTHENTICATE_AUTHENTICATORS_GET, authenticatorsSet } from "./actions";

// Logging helper HOC to make code a little more readable
const logResponse = prefix => {
    return response => {
        log(prefix, response);
        return Promise.resolve(response);
    };
};

/**
 * Get the list of available authenticators.
 *
 * @param {Observable} action$ An action observable.
 *
 * @returns {Observable}
 */
const authenticatorsGet = action$ =>
    action$
        .ofType(AUTHENTICATE_AUTHENTICATORS_GET)
        .throttleTime(200) // drops requests *after* the first one for 200ms, useful if this action called multiple times
        .mergeMap(() =>
            apiv2
                .get("/authenticate/authenticators")
                .then(logResponse("GET /authenticate/authenticators - response: "))
                .then(authenticatorsSet)
                .catch(error => {
                    logError("ERROR /authenticate/authenticators - response: ", error);
                }),
        );

// all of the epics above need to be exported in the combineEpics function below
export default combineEpics(authenticatorsGet);
