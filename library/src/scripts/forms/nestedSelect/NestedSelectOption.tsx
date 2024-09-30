/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { getSearchMatch, INestedSelectOptionProps, nestedSelectClasses } from "@library/forms/nestedSelect";
import { Icon } from "@vanilla/icons";

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
        tooltip,
        highlighted,
    } = props;
    const classes = props.classes ?? nestedSelectClasses();
    const isRootHeader = depth === 0 && !group;

    const handleOptionClick = () => {
        if (onClick && value) {
            onClick(value);
        }
    };

    return (
        <>
            {isRootHeader && isNested && <li className={classes.menuSeparator} role="separator" />}
            <li
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
        </>
    );
}
