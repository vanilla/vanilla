import { applyTableOverflowFade, responsifyTable } from "@library/content/UserContent";

/**
 * Ensure user content tables are responsive if not customized through rich table UI.
 */
export function initTables() {
    const tableNodes: NodeListOf<HTMLTableElement> = document.querySelectorAll(
        ".tableWrapper:not(.customized) table:not(table[data-responsive])",
    );
    const customizedTableNodes: NodeListOf<HTMLTableElement> = document.querySelectorAll(
        ".tableWrapper.customized table:not(table[data-responsive])",
    );
    tableNodes.forEach((tableNode) => {
        tableNode.setAttribute("data-responsive", "");
        responsifyTable(tableNode);
    });
    customizedTableNodes.forEach((tableNode) => {
        tableNode.setAttribute("data-responsive", "");
        applyTableOverflowFade(tableNode);
    });
}
