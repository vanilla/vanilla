import { AUTHENTICATE_AUTHENTICATORS_SET } from "./actions";

const initialState = {
    authenticators: [],
};

const authenticateReducer = (state = initialState, action) => {
    switch (action.type) {
        case AUTHENTICATE_AUTHENTICATORS_SET:
            return {
                ...state,
                authenticators: action.payload,
            };

        default:
            return state;
    }
};

export default authenticateReducer;
