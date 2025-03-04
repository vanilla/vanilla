/**
 * Download data as a file to the clients browser
 * Defaults to CSV download
 */
export const downloadAsFile = (
    data: string | Blob,
    filename: string,
    opts: { fileExtension?: "csv" | "json" | "svg" | "zip" } = { fileExtension: "csv" },
) => {
    const fileType = extensionToMime(opts.fileExtension);
    const blob = data instanceof Blob ? data : new Blob(Array.isArray(data) ? data : [data], { type: fileType });

    // To add IE support, we would need to alternate between using
    // `URL.createObjectURL` and `window.navigator.msSaveOrOpenBlob` here

    // To add support for files > 500mb we would have to try something else.

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.setAttribute("href", url);
    link.setAttribute("download", `${filename}.${opts.fileExtension}`);
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    URL.revokeObjectURL(url);
    document.body.removeChild(link);
};

function extensionToMime(extension: string | undefined): string | undefined {
    switch (extension) {
        case "csv":
            return "text/csv;charset=utf-8;";
        case "json":
            return "application/json;charset=utf-8;";
        case "svg":
            return "image/svg+xml;charset=utf-8;";
        case "zip":
            return "application/zip;charset=utf-8;";
        default:
            undefined;
    }
}
