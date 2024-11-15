/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { openApiClasses } from "@library/openapi/OpenApiClasses";

export function OpenApiEnumList(props: { enumValues: string[] }) {
    const classes = openApiClasses();
    return (
        <ul className={classes.enumList}>
            {props.enumValues.map((value) => (
                <li className={classes.enumItem} key={value}>
                    {value}
                </li>
            ))}
        </ul>
    );
}
