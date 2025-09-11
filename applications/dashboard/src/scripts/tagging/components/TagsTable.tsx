/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import DashboardListPageClasses from "@dashboard/components/DashboardListPage.classes";
import StackableTable, {
    CellRendererProps,
    StackableTableColumnsConfig,
    StackableTableSortOption,
} from "@dashboard/tables/StackableTable/StackableTable";
import DeleteTag from "@dashboard/tagging/features/DeleteTag";
import EditTag from "@dashboard/tagging/features/EditTag";
import TagScopes from "@dashboard/tagging/components/TagScopes";
import TagsTableClasses from "@dashboard/tagging/components/TagsTable.classes";
import { IGetTagsRequestBody, ITagItem } from "@dashboard/tagging/taggingSettings.types";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";

export enum TagsColumnNames {
    TAG_NAME = "tagName",
    USAGE = "usage",
    SCOPE = "scope",
    DATE_INSERTED = "dateInserted",
}

interface IProps {
    tags: ITagItem[];
    isLoading: boolean;
    sort: IGetTagsRequestBody["sort"];
    onSortChange: (sort: IGetTagsRequestBody["sort"]) => void;

    onMutateSuccess?: () => Promise<void>;

    scopeEnabled?: boolean;
}

export default function TagsTable(props: IProps) {
    const { table: tableClass, tableActionButtons, alignRight } = DashboardListPageClasses.useAsHook();
    const { scopeCellWrapper } = TagsTableClasses.useAsHook();

    const { isLoading, tags: data, sort, onSortChange, onMutateSuccess, scopeEnabled = false } = props;

    const columnsConfiguration: StackableTableColumnsConfig = {
        [TagsColumnNames.TAG_NAME]: {
            label: t("Tag Name"),
            order: 1,
            wrapped: false,

            isHidden: false,
            sortDirection: sort
                ? {
                      name: StackableTableSortOption.DESC,
                      "-name": StackableTableSortOption.ASC,
                  }[sort] ?? StackableTableSortOption.NO_SORT
                : StackableTableSortOption.NO_SORT,
        },
        [TagsColumnNames.USAGE]: {
            label: t("Usage"),
            order: 2,

            wrapped: false,
            isHidden: false,
            sortDirection: sort
                ? {
                      countDiscussions: StackableTableSortOption.ASC,
                      "-countDiscussions": StackableTableSortOption.DESC,
                  }[sort] ?? StackableTableSortOption.NO_SORT
                : StackableTableSortOption.NO_SORT,
        },
        ...(scopeEnabled && {
            [TagsColumnNames.SCOPE]: {
                width: 250,
                label: t("Scope"),
                order: 3,
                wrapped: false,
                isHidden: false,
            },
        }),
        [TagsColumnNames.DATE_INSERTED]: {
            label: t("Date Created"),
            order: 4,
            wrapped: false,
            isHidden: false,
            sortDirection: sort
                ? {
                      dateInserted: StackableTableSortOption.ASC,
                      "-dateInserted": StackableTableSortOption.DESC,
                  }[sort] ?? StackableTableSortOption.NO_SORT
                : StackableTableSortOption.NO_SORT,
        },
    };

    function CellRenderer(props: CellRendererProps) {
        const { data, columnName } = props;
        const tagItem = data as ITagItem;

        switch (columnName) {
            case TagsColumnNames.TAG_NAME: {
                return <SmartLink to={`/discussions?tagID=${tagItem.tagID}`}>{tagItem.name}</SmartLink>;
            }
            case TagsColumnNames.USAGE: {
                return (
                    <SmartLink to={`/discussions?tagID=${tagItem.tagID}`}>
                        <span>{tagItem.countDiscussions ?? 0}</span>
                    </SmartLink>
                );
            }
            case TagsColumnNames.SCOPE: {
                return (
                    <span className={scopeCellWrapper}>
                        <TagScopes tagItem={tagItem} />
                    </span>
                );
            }
            case TagsColumnNames.DATE_INSERTED: {
                return tagItem.dateInserted ? <DateTime date={tagItem.dateInserted} /> : <></>;
            }
            default:
                return <></>;
        }
    }

    function ActionsCellRenderer({ data: tag }: { data: ITagItem }) {
        return (
            <div className={cx(tableActionButtons, alignRight)}>
                <EditTag tag={tag} onSuccess={async () => await onMutateSuccess?.()} scopeEnabled={scopeEnabled} />
                <DeleteTag tag={tag} onSuccess={async () => await onMutateSuccess?.()} />
            </div>
        );
    }

    function WrappedCellRenderer(props: { orderedColumns: string[]; configuration: object; data: any }) {
        let result = <></>;
        if (props && props.orderedColumns && props.configuration && props.data)
            props.orderedColumns.forEach((columnName, index) => {
                if (!props.configuration[columnName].hidden && props.configuration[columnName].wrapped) {
                    result = (
                        <>
                            {index !== 0 && result}
                            <CellRenderer
                                data={props.data}
                                columnName={columnName}
                                wrappedVersion={props.configuration[columnName].wrapped}
                            />
                        </>
                    );
                }
            });

        return result;
    }
    const handleSort = (columnName: TagsColumnNames, sortOption: StackableTableSortOption) => {
        if (columnName && sortOption) {
            let sortValue: IGetTagsRequestBody["sort"] | undefined;
            const isDescending = sortOption === StackableTableSortOption.DESC;
            switch (columnName) {
                case TagsColumnNames.TAG_NAME:
                    sortValue = isDescending ? "name" : "-name";
                    break;
                case TagsColumnNames.USAGE:
                    sortValue = isDescending ? "-countDiscussions" : "countDiscussions";
                    break;
                case TagsColumnNames.DATE_INSERTED:
                    sortValue = isDescending ? "-dateInserted" : "dateInserted";
                    break;
            }
            if (sortValue) {
                onSortChange(sortValue);
            }
        }
    };

    return (
        <StackableTable
            className={tableClass}
            data={data}
            onHeaderClick={handleSort}
            isLoading={isLoading}
            loadSize={5}
            hiddenHeaders={["actions"]}
            columnsConfiguration={columnsConfiguration}
            CellRenderer={CellRenderer}
            WrappedCellRenderer={WrappedCellRenderer}
            actionsColumnWidth={100}
            ActionsCellRenderer={ActionsCellRenderer}
        />
    );
}
