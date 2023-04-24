import { responsifyTable } from "@library/content/UserContent";

/**
 * Ensure user content tables are responsive
 */
export function initTables() {
    const tableNodes: NodeListOf<HTMLTableElement> = document.querySelectorAll(
        ".tableWrapper table:not(table[data-responsive])",
    );
    tableNodes.forEach((tableNode) => {
        tableNode.setAttribute("data-responsive", "");
        responsifyTable(tableNode);
    });
}
