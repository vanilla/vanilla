/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import shuffle from "lodash/shuffle";
import { Table as TableComponent } from "./Table";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

export default {
    title: "Dashboard/Analytics",
};

type FauxData = {
    type: "number" | "string" | "node" | "button";
    column: string;
};

interface IFauxDataProps {
    rows?: number;
    columns?: FauxData[];
}
const makeFauxData = (config: IFauxDataProps) => {
    const {
        rows = 10,
        columns = [
            { column: "key1", type: "number" },
            { column: "key2", type: "string" },
        ],
    } = config || {};

    const createDataPoint = (type: string) => {
        switch (type) {
            case "number": {
                return (Math.random() * 10).toFixed(2);
            }
            case "string": {
                return Math.random()
                    .toString(36)
                    .replace(/[^a-z]+/g, "")
                    .substr(0, 12);
            }
            case "button": {
                return (
                    <Button buttonType={ButtonTypes.ICON}>
                        <Icon icon="dashboard-edit" />
                    </Button>
                );
            }
            default: {
                return <span>I am span element</span>;
            }
        }
    };

    return [...Array(rows)].map(() => {
        return Object.fromEntries(columns.map((item) => [item.column, createDataPoint(item.type)]));
    });
};

function BasicTableStory() {
    const fauxData = React.useMemo(
        () =>
            makeFauxData({
                rows: 15,
                columns: [
                    { type: "string", column: "Name" },
                    { type: "number", column: "Dimension One" },
                    { type: "number", column: "Dimension Two" },
                    { type: "node", column: "Dimension Three" },
                ],
            }),
        [],
    );
    return <TableComponent data={fauxData} />;
}

function CustomColumnsOrderTableStory() {
    const [order, setOrder] = React.useState(["A", "B", "C", "D"]);
    const fauxData = React.useMemo(
        () =>
            makeFauxData({
                rows: 15,
                columns: [
                    { type: "number", column: "A" },
                    { type: "number", column: "B" },
                    { type: "number", column: "C" },
                    { type: "number", column: "D" },
                ],
            }),
        [],
    );
    return (
        <>
            <TableComponent data={fauxData} customColumnOrder={order} />
            <Button onClick={() => setOrder((prevState) => shuffle(prevState))}>Shuffle Column Order</Button>
        </>
    );
}

function ColumnsSortableTableStory() {
    const [order, setOrder] = React.useState(["A", "B", "C", "D"]);
    const fauxData = React.useMemo(
        () =>
            makeFauxData({
                rows: 15,
                columns: [
                    { type: "string", column: "A" },
                    { type: "number", column: "B" },
                    { type: "number", column: "C" },
                    { type: "number", column: "D" },
                ],
            }),
        [],
    );
    return (
        <>
            <StoryParagraph>Click on a column header to sort by it</StoryParagraph>
            <TableComponent data={fauxData} sortable={true} />
        </>
    );
}

function PresetSortableTableStory() {
    const fauxData = React.useMemo(
        () =>
            makeFauxData({
                rows: 15,
                columns: [
                    { type: "string", column: "A" },
                    { type: "number", column: "B" },
                    { type: "number", column: "C" },
                    { type: "number", column: "D" },
                ],
            }),
        [],
    );
    return <TableComponent data={fauxData} sortable={true} customColumnSort={{ id: "C", desc: false }} />;
}

function PaginatedTableStory() {
    const fauxData = React.useMemo(
        () =>
            makeFauxData({
                rows: 50,
                columns: [
                    { type: "string", column: "Name" },
                    { type: "number", column: "Page Views" },
                    { type: "number", column: "TTI" },
                    { type: "number", column: "Conversion Ratio" },
                ],
            }),
        [],
    );
    return <TableComponent data={fauxData} paginate={true} pageSize={10} />;
}

function HiddenColumnHeaderTableStory() {
    const fauxData = React.useMemo(
        () =>
            makeFauxData({
                columns: [
                    { type: "number", column: "ID" },
                    { type: "string", column: "Name" },
                    { type: "node", column: "Description" },
                    { type: "string", column: "label" },
                    { type: "button", column: "Actions" },
                ],
            }),
        [],
    );

    return (
        <>
            <StoryParagraph>
                Last column has the header hidden on display, but the title &quot;Actions&quot; is accessible to screen
                readers.
            </StoryParagraph>
            <TableComponent data={fauxData} hiddenHeaders={["Actions"]} />
        </>
    );
}

export function BasicTable() {
    return <BasicTableStory />;
}
export function CustomColumnsOrderTable() {
    return <CustomColumnsOrderTableStory />;
}
export function ColumnsSortableTable() {
    return <ColumnsSortableTableStory />;
}
export function PresetSortableTable() {
    return <PresetSortableTableStory />;
}
export function PaginatedTable() {
    return <PaginatedTableStory />;
}

export function HiddenColumnHeaderTable() {
    return <HiddenColumnHeaderTableStory />;
}
