/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// A weakmap so we can store multiple load callbacks per script.
const loadEventCallbacks: WeakMap<Node, Array<(event) => void>> = new WeakMap();
const rejectionCache: Map<string, Error> = new Map();

/**
 * Dynamically load a javascript file.
 */
export function ensureScript(scriptUrl: string): Promise<void> {
    return new Promise((resolve, reject) => {
        const existingScript: HTMLScriptElement | null = document.querySelector(`script[src='${scriptUrl}']`);
        if (rejectionCache.has(scriptUrl)) {
            reject(rejectionCache.get(scriptUrl));
        }
        if (existingScript) {
            if (loadEventCallbacks.has(existingScript)) {
                // Add another resolveCallback into the weakmap.
                const callbacks = loadEventCallbacks.get(existingScript);
                callbacks && callbacks.push(resolve);
            } else {
                // Script is already loaded. Resolve immediately.
                resolve();
            }
        } else {
            // The script doesn't exist. Lets create it.
            const head = document.getElementsByTagName("head")[0];
            const script = document.createElement("script");
            script.type = "text/javascript";
            script.src = scriptUrl;
            script.onerror = (event: ErrorEvent) => {
                const error = new Error("Failed to load a required embed script");
                rejectionCache.set(scriptUrl, error);
                reject(error);
            };

            const timeout = setTimeout(() => {
                const error = new Error(`Loading of the script ${scriptUrl} has timed out.`);
                rejectionCache.set(scriptUrl, error);
                reject(error);
            }, 10000);

            loadEventCallbacks.set(script, [resolve]);

            script.onload = event => {
                clearTimeout(timeout);
                const callbacks = loadEventCallbacks.get(script);
                callbacks && callbacks.forEach(callback => callback(event));
                loadEventCallbacks.delete(script);
            };

            head.appendChild(script);
        }
    });
}
