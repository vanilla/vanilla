/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useMemo, useState } from "react";

export function useStatefulRef<T>(initial: T): React.MutableRefObject<T> {
    const [stateValue, setStateValue] = useState(initial);
    const refValue = useMemo(() => {
        return {
            _current: initial,
            get current() {
                return this._current;
            },
            set current(value: T) {
                if (this._current !== value) {
                    setStateValue(value);
                }
                this._current = value;
            },
        };
    }, []);
    return refValue;
}
