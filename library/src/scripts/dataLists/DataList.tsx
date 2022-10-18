/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";

import { dataListClasses } from "@library/dataLists/dataListStyles";
import { RecordID } from "@vanilla/utils";
import { TokenItem } from "@library/metas/TokenItem";
import { cx } from "@emotion/css";
import CheckBox from "@library/forms/Checkbox";
import { t } from "@vanilla/i18n";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { RequireAtLeastOne } from "@library/@types/api/core";
import Heading from "@library/layout/Heading";

export interface IDataListNode {
    key: React.ReactNode;
    value: React.ReactNode | RecordID | RecordID[];
}

interface DataListProps {
    className?: string;
    data: IDataListNode[];
    isLoading?: boolean;
    loadingRows?: number;
    /** Required for accessibility. Should give you a short summary of the contents and is not visible on the screen */
    caption?: string;
    /** Optional and will be rendered on the screen */
    title?: string;
}

type IProps = RequireAtLeastOne<DataListProps, "title" | "caption">;

/**
 * Component for displaying data lists
 * Because of accessibility concerns, the markup is a table not a data list.
 */
export function DataList(props: IProps) {
    const { data, caption, title, className, isLoading, loadingRows } = props;
    const classes = dataListClasses();

    const formattedData = useMemo<IDataListNode[] | null>(() => {
        if (!isLoading && data && data.length > 0) {
            return data.map((dataListNode) => {
                // Arrays displayed as tokens
                if (Array.isArray(dataListNode.value)) {
                    return {
                        ...dataListNode,
                        value: (
                            <div className={classes.tokenGap}>
                                {dataListNode.value.map((arrayEntry: RecordID, index) => (
                                    <TokenItem key={`${arrayEntry}${index}`}>{arrayEntry}</TokenItem>
                                ))}
                            </div>
                        ),
                    };
                }
                // Special handling for boolean values
                if (typeof dataListNode.value === "boolean") {
                    return {
                        ...dataListNode,
                        value: (
                            <CheckBox
                                className={classes.checkBoxAlignment}
                                checked={!!dataListNode.value}
                                label={dataListNode.value ? t("Yes") : t("No")}
                                labelBold={false}
                                disabled
                            />
                        ),
                    };
                }
                return dataListNode;
            });
        } else if (isLoading) {
            return Array.from(new Array(loadingRows ?? 5))
                .fill(" ")
                .map(() => ({
                    key: <LoadingRectangle width={`${Math.floor(Math.random() * (50 - 25) + 25)}`} />,
                    value: <LoadingRectangle width={`${Math.floor(Math.random() * (90 - 25) + 25)}`} />,
                }));
        }
        return null;
    }, [data, isLoading]);

    return (
        <div className={cx(classes.root, className)}>
            {title && (
                <Heading className={classes.title} renderAsDepth={3}>
                    {title}
                </Heading>
            )}
            <table className={classes.table}>
                <caption>{caption ?? title}</caption>
                <tbody>
                    {formattedData &&
                        formattedData.map((dataListNode, index) => {
                            return (
                                <tr
                                    className={cx(
                                        {
                                            ["isFirst"]: index === 0,
                                        },
                                        {
                                            ["isLast"]: index === formattedData.length,
                                        },
                                    )}
                                    key={index}
                                >
                                    <th scope="row" className={classes.key}>
                                        {dataListNode.key}
                                    </th>
                                    <td className={classes.value}>{dataListNode.value}</td>
                                </tr>
                            );
                        })}
                </tbody>
            </table>
        </div>
    );
}
