/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { GridSelector, GridSelectorLayout } from "@library/forms/gridSelector/GridSelector";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { TextInput } from "@library/forms/TextInput";
import { useState } from "react";

export default {
    title: "Components/Grid Selector",
    parameters: {},
};

export function ConfigurableGridSelector() {
    const defaultGridLayout = { colCount: 5, rowCount: 6 };
    const [gridLayoutConfig, setGridLayoutConfig] = useState({ colCount: "", rowCount: "" });
    const [selection, setSelection] = useState<GridSelectorLayout | undefined>();

    return (
        <div style={{ maxWidth: 300, margin: 60 }}>
            <StoryHeading depth={1}>Grid Selector</StoryHeading>
            <StoryParagraph>
                Grid Items size will adjust according to available space and column/row layout. Click on grid items to
                see your selection.
            </StoryParagraph>
            <div style={{ display: "flex", gap: 16 }}>
                <div>
                    <div>Columns: </div>
                    <TextInput
                        type="number"
                        value={gridLayoutConfig.colCount}
                        onChange={(e) => {
                            setGridLayoutConfig({
                                ...gridLayoutConfig,
                                colCount: e.target.value,
                            });
                        }}
                    />
                </div>
                <div>
                    <div>Rows: </div>
                    <TextInput
                        type="number"
                        value={gridLayoutConfig.rowCount}
                        onChange={(e) => {
                            setGridLayoutConfig({
                                ...gridLayoutConfig,
                                rowCount: e.target.value,
                            });
                        }}
                    />
                </div>
            </div>
            <GridSelector
                gridLayout={{
                    colCount: Number(gridLayoutConfig.colCount) || defaultGridLayout.colCount,
                    rowCount: Number(gridLayoutConfig.rowCount) || defaultGridLayout.rowCount,
                }}
                onSelect={(layout) => {
                    setSelection(layout);
                }}
            />
            <div>{`Current selection is ${selection?.colCount ?? 0} columns and ${selection?.rowCount ?? 0} rows`}</div>
        </div>
    );
}
