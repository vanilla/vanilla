/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { gridSelectorClasses } from "@library/forms/gridSelector/GridSelector.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { cx } from "@emotion/css";
import { useMemo, useState } from "react";

export interface GridSelectorLayout {
    colCount: number;
    rowCount: number;
}

interface IProps {
    minSelection?: GridSelectorLayout;
    gridLayout?: GridSelectorLayout;
    onSelect?: (tableLayout: GridSelectorLayout) => void;
}

export function GridSelector(props: IProps) {
    const { gridLayout = { colCount: 5, rowCount: 6 }, minSelection } = props;
    const classes = gridSelectorClasses(gridLayout);

    const itemsNumber = gridLayout.rowCount * gridLayout.colCount;

    const [activeGridItemIndex, setActiveGridItemIndex] = useState<number | null>(null);

    const currentPosition = useMemo(() => {
        if (activeGridItemIndex !== null) {
            return {
                rowIndex: Math.floor(activeGridItemIndex / gridLayout.colCount),
                colIndex: activeGridItemIndex % gridLayout.colCount,
            };
        }
        return {};
    }, [activeGridItemIndex]);

    const highlightedItemsIndexes = useMemo(() => {
        if (!activeGridItemIndex) {
            return [];
        }

        const indexes: number[] = [];

        Array(itemsNumber)
            .fill(null)
            .forEach((_, index) => {
                const shouldInclude =
                    index < activeGridItemIndex && index % gridLayout.colCount <= (currentPosition.colIndex ?? 0);
                if (shouldInclude) {
                    indexes.push(index);
                }
            });

        return indexes;
    }, [activeGridItemIndex, currentPosition]);

    return (
        <div
            className={classes.gridLayout}
            onMouseLeave={() => {
                setActiveGridItemIndex(null);
            }}
        >
            {Array(itemsNumber)
                .fill(null)
                .map((_, index) => {
                    const isSelectable = minSelection
                        ? Math.floor(index / gridLayout.colCount) >= minSelection.rowCount - 1 &&
                          index % gridLayout.colCount >= minSelection.colCount - 1
                        : true;
                    const commonProps = {
                        key: index,
                        id: `${index}`,
                        className: cx(classes.gridItem, {
                            [classes.gridItemHighlighted]: highlightedItemsIndexes.includes(index),
                            [classes.nonSelectable]: !isSelectable,
                        }),
                        onMouseEnter: () => setActiveGridItemIndex(index),
                        onFocus: () => setActiveGridItemIndex(index),
                    };

                    return isSelectable ? (
                        <Button
                            ariaLabel={t(
                                `Insert a table with ${currentPosition.colIndex! + 1} columns and ${
                                    currentPosition.rowIndex! + 1
                                } rows`,
                            )}
                            {...commonProps}
                            buttonType={ButtonTypes.CUSTOM}
                            onClick={() =>
                                props.onSelect?.({
                                    colCount: currentPosition.colIndex! + 1,
                                    rowCount: currentPosition.rowIndex! + 1,
                                })
                            }
                        ></Button>
                    ) : (
                        <div {...commonProps}></div>
                    );
                })}
        </div>
    );
}
