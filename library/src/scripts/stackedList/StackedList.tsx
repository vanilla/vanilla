import React, { ComponentType } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { stackedListClasses } from "@library/stackedList/StackedList.styles";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { t } from "@library/utility/appUtils";
import NumberFormatted from "@library/content/NumberFormatted";
import Button from "@library/forms/Button";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { cx } from "@emotion/css";

interface IStackedListProps<T extends {}> {
    themingVariables: ReturnType<typeof stackedListVariables>;
    data: T[];
    maxCount?: number;
    extra?: number;
    openModal?(): void;
    tooltipText?: string;
    ItemComponent: ComponentType<T & JSX.IntrinsicAttributes>;
    className?: string;
}

export function StackedList<T extends {}>(props: IStackedListProps<T>) {
    const { themingVariables, data, extra = 0, maxCount = Infinity, openModal, tooltipText, ItemComponent } = props;
    const { item: itemClass, lastItem: lastItemClass, root, plusLink } = stackedListClasses(themingVariables);
    const extraCount = Math.max(0, data.length + extra - maxCount);
    const itemsToDisplay = data.slice(0, maxCount);
    return (
        <ul className={cx(root, props.className)}>
            {itemsToDisplay.map((item, i) => {
                const isLastItem = i === itemsToDisplay.length - 1 || i === maxCount - 1;
                return (
                    <li className={isLastItem ? lastItemClass : itemClass} key={i}>
                        <ItemComponent {...item} />
                    </li>
                );
            })}
            {extraCount > 0 && (
                <li className={plusLink} key={data.length}>
                    <ConditionalWrap
                        condition={!!openModal}
                        component={Button}
                        componentProps={{ onClick: openModal, buttonType: ButtonTypes.TEXT }}
                    >
                        <span style={{ display: "inline-block" }}>
                            <>
                                +
                                <NumberFormatted value={extraCount} title={tooltipText ? t(tooltipText) : undefined} />
                            </>
                        </span>
                    </ConditionalWrap>
                </li>
            )}
        </ul>
    );
}
