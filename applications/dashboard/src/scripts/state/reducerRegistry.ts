/**
 * A reducer registry so that we can have dynamically loading reducers.
 *
 * @see http://nicolasgallagher.com/redux-modules-and-code-splitting/
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

type ChangeEmitter = (
    reducers: {
        [key: string]: any;
    },
) => void;

export class ReducerRegistry {
    private emitChange?: ChangeEmitter;
    private reducers = {};

    public getReducers() {
        return this.reducers;
    }

    public register(name, reducer) {
        this.reducers = { ...this.reducers, [name]: reducer };
        if (this.emitChange) {
            this.emitChange(this.getReducers());
        }
    }

    public setChangeListener(listener: ChangeEmitter) {
        this.emitChange = listener;
    }
}

const reducerRegistry = new ReducerRegistry();
export default reducerRegistry;
