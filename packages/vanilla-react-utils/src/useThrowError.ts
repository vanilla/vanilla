/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useState } from "react";

/**
 * Return a throw function for use inside of custom hooks & useEffect.
 *
 * This needed because throwing inside of `useEffect` does not propagate up to error boundaries.
 * @see https://github.com/facebook/react/issues/11334
 */
export function useThrowError(): (err: Error) => void {
    const [error, setError] = useState<Error | null>(null);
    if (error) {
        throw error;
    }

    return setError;
}
