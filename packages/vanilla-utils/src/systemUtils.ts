/**
 * Utilities related to the user's system.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export enum OS {
    IOS = "ios",
    ANDROID = "android",
    UNKNOWN = "unkwown",
}

/**
 * Provide relatively rough detection of mobile OS.
 *
 * This is not even close to perfect but can be used to try and offer,
 * OS specific input elements for things like datetimes.
 */
export function guessOperatingSystem(): OS {
    const userAgent = navigator.userAgent || navigator.vendor || (window as any).opera;

    if (/android/i.test(userAgent)) {
        return OS.ANDROID;
    }

    // iOS detection from: http://stackoverflow.com/a/9039885/177710
    if (/iPad|iPhone|iPod/.test(userAgent) && !(window as any).MSStream) {
        return OS.IOS;
    }

    return OS.UNKNOWN;
}
