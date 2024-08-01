import { logError } from "@vanilla/utils";

export async function mountAllEmbeds(root: HTMLElement = document.body) {
    const mountPoints = root.querySelectorAll(".js-embed[data-embedjson]");

    const promises: Array<Promise<any>> = [];

    for (const mountPoint of Array.from(mountPoints)) {
        try {
            const parsedData = JSON.parse(mountPoint.getAttribute("data-embedjson") || "{}");
            const mountEmbed = await import("./embedService.loadable").then((m) => m.mountEmbed);
            promises.push(mountEmbed(mountPoint as HTMLElement, parsedData, false));
        } catch (e) {
            logError("failed to mountEmbed", { error: e, mountPoint });
            return Promise.resolve();
        }
    }
    await Promise.all(promises);
}
