/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { StackableTableColumnsConfig, adjustColumnsVisibility } from "@dashboard/tables/StackableTable/StackableTable";

describe("StackableTable", () => {
    const mockColumnsConfig: StackableTableColumnsConfig = {
        "first column": {
            order: 1,
            wrapped: false,
            isHidden: false,
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
        "sixth column": {
            order: 6,
            wrapped: false,
            isHidden: false,
        },
    };
    it("Test columns stacking with adjustColumnsVisibility() function", () => {
        const adjustedConfig = adjustColumnsVisibility(mockColumnsConfig, Object.keys(mockColumnsConfig), 700, 20);

        //we had six columns initially, 2nd, 3rd and 4th should have wrapped = true as a result of configuration adjustment
        const wrappedColumns = Object.keys(adjustedConfig).filter((column) => adjustedConfig[column].wrapped);
        expect(wrappedColumns.length).toBe(3);
        expect(wrappedColumns.includes("second column"));
        expect(wrappedColumns.includes("third column"));
        expect(wrappedColumns.includes("forth column"));

        //last two columns have custom, smaller width, we should have less(only 1) columns wrapped
        const newMockColumnsConfig = {
            ...mockColumnsConfig,
            "fifth column": {
                ...mockColumnsConfig["fifth column"],
                width: 30,
            },
            "sixth column": {
                ...mockColumnsConfig["sixth column"],
                width: 30,
            },
        };
        const newAdjustedConfig = adjustColumnsVisibility(
            newMockColumnsConfig,
            Object.keys(newMockColumnsConfig),
            700,
            20,
        );
        const newWrappedColumns = Object.keys(newAdjustedConfig).filter((column) => adjustedConfig[column].wrapped);
        expect(newWrappedColumns.length).toBe(1);
        expect(wrappedColumns.includes("second column"));
    });
});
