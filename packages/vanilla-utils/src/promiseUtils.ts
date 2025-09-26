/**
 * Utility functions related to promises/async.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

type NormalCallback = (...args: any[]) => any;
type PromiseCallback = (...args: any[]) => Promise<any>;

export type PromiseOrNormalCallback = NormalCallback | PromiseCallback;

/**
 * Resolve an array of functions that return promises sequentially.
 *
 * @param promiseFunctions - The functions to execute.
 *
 * @returns An array of all results in sequential order.
 *
 * @example
 * const urls = ['/url1', '/url2', '/url3']
 * const functions = urls.map(url => () => fetch(url))
 * resolvePromisesSequentially(funcs)
 *   .then(console.log)
 *   .catch(console.error)
 */
export function resolvePromisesSequentially(promiseFunctions: PromiseOrNormalCallback[]): Promise<any[]> {
    if (!Array.isArray(promiseFunctions)) {
        throw new Error("First argument needs to be an array of Promises");
    }

    return new Promise((resolve, reject) => {
        let count = 0;
        let results = [];

        function iterationFunction(previousPromise, currentPromise) {
            return previousPromise
                .then((result) => {
                    if (count++ !== 0) {
                        results = results.concat(result);
                    }

                    return currentPromise(result, results, count);
                })
                .catch((err) => reject(err));
        }

        promiseFunctions = promiseFunctions.concat(() => Promise.resolve());

        promiseFunctions.reduce(iterationFunction, Promise.resolve(false)).then(() => {
            resolve(results);
        });
    });
}

type Awaited<T> = T extends Promise<infer U> ? U : T;

type ResolvedObject<T> = {
    [K in keyof T]: Awaited<T[K]>;
};

type CallObject<T extends Record<string, () => any>> = {
    [K in keyof T]: ReturnType<T[K]>;
};

function callObjectMethods<T extends Record<string, () => any>>(obj: T): CallObject<T> {
    const entries = Object.entries(obj).map(([k, v]) => [k, v()] as const);

    return Object.fromEntries(entries) as CallObject<T>;
}

export async function resolveObject<T extends Record<string, any>>(obj: T): Promise<ResolvedObject<T>> {
    const entries = await Promise.allSettled(Object.entries(obj).map(async ([k, v]) => [k, await v] as const));

    const resolvedEntries = entries.map((result, index) => {
        const [key] = Object.entries(obj)[index];
        if (result.status === "fulfilled") {
            return [key, result.value[1]];
        } else {
            console.error(`Promise for key "${key}" was rejected:`, result.reason);
            return [key, undefined]; // or handle the rejection as needed
        }
    });

    return Object.fromEntries(resolvedEntries) as ResolvedObject<T>;
}

export async function resolveImports<T extends Record<string, () => Promise<any>>>(
    obj: T,
): Promise<ResolvedObject<CallObject<T>>> {
    return await resolveObject(callObjectMethods(obj));
}
