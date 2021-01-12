export function camelCaseToDash(str: string) {
    return str.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
}
