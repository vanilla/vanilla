/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useReportReasons } from "@dashboard/moderation/CommunityManagement.hooks";
import { FilterBlock, FilterBlockProps } from "@dashboard/moderation/components/FilterBlock";
import { useMemo } from "react";

type IProps = {} & Omit<FilterBlockProps, "staticOptions">;

/**
 * Filter block with reasons for reports
 */
export function ReasonFilter(props: IProps) {
    const { isLoading, reasons } = useReportReasons({ includeSystem: true });

    const reasonOptions = useMemo(() => {
        if (isLoading) {
            return [];
        }
        return (
            reasons?.data?.map((reason) => ({
                name: reason.name,
                value: reason.reportReasonID,
            })) ?? []
        );
    }, [reasons, isLoading]);
    return (
        <>
            {!isLoading && (
                <FilterBlock
                    apiName={props.apiName}
                    label={props.label}
                    initialFilters={props.initialFilters}
                    staticOptions={reasonOptions}
                    onFilterChange={props.onFilterChange}
                />
            )}
        </>
    );
}
