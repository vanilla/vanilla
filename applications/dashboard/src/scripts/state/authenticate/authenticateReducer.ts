import { combineReducers } from "redux";
import { signinReducer } from "@dashboard/state/authenticate/authenticatorReducer";
import { LoadStatus } from "@dashboard/state/IState";

const authenticateReducer = combineReducers({
    signin: signinReducer,
    profile: (s = []) => ({ status: LoadStatus.PENDING, data: [] }),
});

export default authenticateReducer;
