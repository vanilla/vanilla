import { combineReducers } from "redux";
import { signinReducer } from "@dashboard/state/authentication/authenticatorReducer";
import { LoadStatus } from "@dashboard/state/authentication/IAuthenticationState";

const authenticationReducer = combineReducers({
    signin: signinReducer,
    profile: (s = []) => ({ status: LoadStatus.PENDING, data: [] }),
});

export default authenticationReducer;
