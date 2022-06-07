declare const customHtmlRoot: Element;

declare namespace VanillaGlobals {
    const apiv2: HttpClient;

    /**
     * Translate a string into the current locale.
     *
     * @param str - The string to translate.
     * @param defaultTranslation - The default translation to use.
     *
     * @returns Returns the translation or the default.
     */
    function translate(str: string, defaultTranslation?: string): string;

    /**
     * Determine if the user has one of the given permissions.
     *
     * - Always false if the data isn't loaded yet.
     * - Always true if the user has the admin flag set.
     * - Only 1 one of the provided permissions needs to match.
     */
    function currentUserHasPermission(permission: string | string[]): boolean;

    /**
     * Get a fragment representing the current user.
     */
    function getCurrentUser(): IUserFragment;

    /**
     * Get the current locale key of the site.
     */
    function getCurrentLocale(): string;

    type VanillaGlobals = {
        apiv2: HttpClient;
        translate: typeof translate;
        currentUserHasPermission: typeof currentUserHasPermission;
        getCurrentUser: typeof getCurrentUser;
        getCurrentLocale: typeof getCurrentLocale;
    };

    type Headers = Record<string, string>;

    interface RequestConfig {
        headers?: Headers;
        params?: Record<string, any>;
        data?: any;
        timeout?: number;
    }

    interface Response {
        data: any;
        status: number;
        statusText: string;
        headers: Headers;
        config: RequestConfig;
    }

    interface HttpError extends Error {
        config: RequestConfig;
        code?: string;
        request?: any;
        response?: Response;
        toJSON: () => object;
    }

    class HttpClient {
        get(url: string, config?: RequestConfig): Promise<Response>;
        delete(url: string, config?: RequestConfig): Promise<Response>;
        head(url: string, config?: RequestConfig): Promise<Response>;
        options(url: string, config?: RequestConfig): Promise<Response>;
        post(url: string, data?: any, config?: RequestConfig): Promise<Response>;
        put(url: string, data?: any, config?: RequestConfig): Promise<Response>;
        patch(url: string, data?: any, config?: RequestConfig): Promise<Response>;
    }

    interface IUserFragment {
        userID: number;
        name: string;
        url?: string;
        photoUrl: string;
        dateLastActive: string | null;
        label?: string;
        title?: string;
        banned?: number;
        private?: boolean;
    }
}

declare const vanilla: VanillaGlobals.VanillaGlobals;
