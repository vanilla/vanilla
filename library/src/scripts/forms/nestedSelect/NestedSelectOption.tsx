/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { getSearchMatch, INestedSelectOptionProps, nestedSelectClasses } from "@library/forms/nestedSelect";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { Hoverable } from "@vanilla/react-utils";

export function NestedSelectOption(props: INestedSelectOptionProps) {
    const {
        id,
        label,
        isHeader,
        depth = 0,
        group,
        isSelected,
        value,
        isNested,
        onClick,
        searchQuery,
        highlighted,
        onHover,
        createableLabel,
        data,
    } = props;
    const classes = props.classes ?? nestedSelectClasses();
    const isRootHeader = depth === 0 && !group;

    const handleOptionClick = () => {
        if (onClick && value) {
            onClick(value);
        }
    };

    const isCreatable = !!props.data?.createable;
    let createableText = createableLabel ?? t("Add Exact Text");
    let tooltip = props.tooltip ?? (isCreatable ? createableText : undefined);

    return (
        <>
            {((isRootHeader && isNested) || isCreatable) && <li className={classes.menuSeparator} role="separator" />}
            <Hoverable onHover={onHover} duration={30}>
                {(hoverProps) => (
                    <li
                        {...hoverProps}
                        className={cx({
                            [classes.menuHeader(isRootHeader, depth)]: isHeader,
                            [classes.menuItem(depth)]: !isHeader,
                            [classes.menuItemSelected]: isSelected,
                            highlighted: highlighted,
                        })}
                        role={isHeader ? "heading" : "menuitem"}
                        onClick={isHeader ? undefined : handleOptionClick}
                        id={isHeader ? undefined : `${id}-${value}`}
                        aria-label={label}
                    >
                        <span className={classes.menuItemLabel}>
                            {searchQuery ? (
                                <>
                                    {tooltip && <span className={classes.menuItemGroup}>{`${tooltip} `}</span>}
                                    <span>
                                        {getSearchMatch(label, searchQuery).parts.map((part, idx) => {
                                            if (idx === 1) {
                                                return <strong key={idx}>{part}</strong>;
                                            }
                                            return <span key={idx}>{part}</span>;
                                        })}
                                    </span>
                                </>
                            ) : (
                                label
                            )}
                        </span>
                        {isSelected && <Icon icon="data-checked" className={classes.menuItemSelectedIcon} />}
                    </li>
                )}
            </Hoverable>
        </>
    );
}
