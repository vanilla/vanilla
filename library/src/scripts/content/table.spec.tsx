import React from "react";
import { act, render } from "@testing-library/react";
import { initTables } from "@library/content/table";

const MOCK_HTML = `<div class="tableWrapper">
    <table>
        <thead>
            <tr>
                <th>Column One</th>
                <th>Column Two</th>
                <th>Column Three</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Row 1 Col 1</td>
                <td>Row 1 Col 2</td>
                <td>Row 1 Col 3</td>
            </tr>
            <tr>
                <td>Row 2 Col 1</td>
                <td>Row 2 Col 2</td>
                <td>Row 2 Col 3</td>
            </tr>
        </tbody>
    </table>
</div>`;

function RenderMockTable() {
    return <section dangerouslySetInnerHTML={{ __html: MOCK_HTML }}></section>;
}

describe("table", () => {
    it("Responsive data attribute is added the table", () => {
        const { container } = render(<RenderMockTable />);
        act(() => {
            initTables();
            const table = container.querySelector("table");
            expect(table).toHaveAttribute("data-responsive");
        });
    });
    it("Table body rows have mobile specific heads added to them", () => {
        const { container } = render(<RenderMockTable />);
        act(() => {
            initTables();
            const tableRow = container.querySelectorAll("tbody tr")[0];
            const injectedHead = tableRow.querySelector("th");
            expect(injectedHead).not.toBeNull();
            expect(injectedHead).toHaveAttribute("aria-hidden", "true");
            expect(injectedHead).toHaveAttribute("class", "mobileTableHead");
        });
    });
    it("Table is only responsified once", () => {
        const { container } = render(<RenderMockTable />);
        act(() => {
            initTables();
            initTables();
            initTables();
            initTables();
            initTables();
            const tableWarppers = container.querySelectorAll(".tableWrapper");
            // 2 because initial render then again 1 time when made responsive
            expect(tableWarppers.length).not.toBeGreaterThan(2);
        });
    });
});
