export function filterQuillHTML(input: string): string {
    // Replace a trailing newline.
    return input
        .replace(/(<(pre|script|style|textarea)[^]+?<\/\2)|(^|>)\s+|\s+(?=<|$)/g, "$1$3")
        .replace(/\<p\>\<br\>\<\/p\>/, "");
}
