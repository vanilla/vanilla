export function trimTrailingCommas(selector) {
    return selector.trim().replace(new RegExp("[,]+$"), "");
}
