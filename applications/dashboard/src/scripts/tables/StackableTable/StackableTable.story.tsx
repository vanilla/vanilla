/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import React from "react";
import StackableTable, { StackableTableSortOption } from "@dashboard/tables/StackableTable/StackableTable";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";

export default {
    title: "Dashboard/UserManagement",
    decorators: [dashboardCssDecorator],
};

const mockColumnsConfiguration = {
    "first column": {
        order: 1,
        wrapped: false,
        isHidden: false,
        sortDirection: StackableTableSortOption.DESC,
    },
    "second column": {
        order: 2,
        wrapped: false,
        isHidden: false,
    },
    "third column": {
        order: 3,
        wrapped: false,
        isHidden: false,
        sortDirection: StackableTableSortOption.DESC,
    },
    "forth column": {
        order: 4,
        wrapped: false,
        isHidden: false,
    },
    "fifth column": {
        order: 5,
        wrapped: false,
        isHidden: false,
    },
};

const mockData = [
    {
        name: "name1",
        somethingElse: "text1",
        count: 2,
    },
    {
        name: "name2",
        somethingElse: "text2",
        count: 5,
    },
    {
        name: "name3",
        somethingElse: "text3",
        count: 3,
    },
    {
        name: "name4",
        somethingElse: "text4",
        count: 8,
    },
    {
        name: "name5",
        somethingElse: "text5",
        count: 5,
    },
];

const MockCellRenderer = (props) => {
    if (props && props.columnName && props.data) {
        switch (props.columnName) {
            case "first column":
                return (
                    <div>
                        {props.wrappedVersion && <span style={{ fontWeight: 700 }}>{`${props.columnName}: `}</span>}
                        {props.data.name}
                    </div>
                );
            case "second column":
                return (
                    <div>
                        {props.wrappedVersion && <span style={{ fontWeight: 700 }}>{`${props.columnName}: `}</span>}
                        {props.data.somethingElse}
                    </div>
                );
            case "third column":
                return (
                    <div>
                        {props.wrappedVersion && <span style={{ fontWeight: 700 }}>{`${props.columnName}: `}</span>}
                        {props.data.count}
                    </div>
                );
            case "forth column":
            case "fifth column":
                return (
                    <div>
                        {props.wrappedVersion && <span style={{ fontWeight: 700 }}>{`${props.columnName}: `}</span>}
                        N/A
                    </div>
                );

            default:
                return <></>;
        }
    }
    return <></>;
};
const MockWrappedCellRenderer = (props) => {
    let result = <></>;
    if (props && props.orderedColumns && props.configuration && props.data)
        props.orderedColumns.forEach((columnName, index) => {
            if (!props.configuration[columnName].hidden && props.configuration[columnName].wrapped) {
                result = (
                    <>
                        {index !== 0 && result}
                        <MockCellRenderer columnName={columnName} data={props.data} wrappedVersion />
                    </>
                );
            }
        });

    return result;
};

const MockActionsRenderer = (props) => {
    return <Button buttonType={ButtonTypes.DASHBOARD_PRIMARY}>Button</Button>;
};

export function StackableTableDesktop() {
    return (
        <>
            <StoryHeading depth={1}>Full Width </StoryHeading>
            <StackableTable
                data={mockData}
                columnsConfiguration={mockColumnsConfiguration}
                updateQuery={() => {}}
                onHeaderClick={() => {}}
                CellRenderer={MockCellRenderer}
                WrappedCellRenderer={MockWrappedCellRenderer}
                ActionsCellRenderer={MockActionsRenderer}
            />
        </>
    );
}

export function StackableTableMobile() {
    return (
        <>
            <StoryHeading depth={1}>Mobile view, with stacked/wrapped columns</StoryHeading>
            <div style={{ width: 1200 }}>
                <div style={{ width: 600 }}>
                    <StackableTable
                        data={mockData}
                        columnsConfiguration={mockColumnsConfiguration}
                        updateQuery={() => {}}
                        onHeaderClick={() => {}}
                        CellRenderer={MockCellRenderer}
                        WrappedCellRenderer={MockWrappedCellRenderer}
                        ActionsCellRenderer={MockActionsRenderer}
                    />
                </div>
            </div>
        </>
    );
}
