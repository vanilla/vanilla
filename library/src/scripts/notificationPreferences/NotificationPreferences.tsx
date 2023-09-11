/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useToast } from "@library/features/toaster/ToastContext";
import Checkbox from "@library/forms/Checkbox";
import Heading from "@library/layout/Heading";
import { PageBox } from "@library/layout/PageBox";
import Paragraph from "@library/layout/Paragraph";
import {
    ColumnType,
    INotificationPreferences,
    NotificationPreferencesContextProvider,
    TableType,
    api,
    useNotificationPreferencesContext,
    utils,
} from "@library/notificationPreferences";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { typographyClasses } from "@library/styles/typographyStyles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { Formik, useFormikContext } from "formik";
import React, { PropsWithChildren, useMemo } from "react";
import { Column, Row, useTable } from "react-table";
import debounce from "lodash/debounce";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { PreferencesTable } from "@library/preferencesTable/PreferencesTable";
import { makeRowDescriptionId } from "@library/notificationPreferences/utils";
import { notificationPreferencesFormClasses } from "@library/preferencesTable/PreferencesTable.styles";
import { RecordID } from "@vanilla/utils";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { AccountConflict } from "@library/accountConflict/AccountConflict";

export default function NotificationPreferences(props: { userID: RecordID }) {
    return (
        <section>
            <NotificationPreferencesContextProvider userID={props.userID} api={api}>
                <Heading className={typographyClasses().largeTitle} depth={1} title={t("Notification Preferences")} />
                <NotificationPreferencesForm debounceInterval={1250} />
            </NotificationPreferencesContextProvider>
            <AccountConflict />
        </section>
    );
}

export function NotificationPreferencesForm(props: { debounceInterval?: number }) {
    const toast = useToast();
    const { schema, preferences, editPreferences } = useNotificationPreferencesContext();

    const { debounceInterval } = props;
    const shouldDebounce = debounceInterval !== undefined && debounceInterval > 0;
    const possiblyDebouncedEditPreferences = useMemo(
        () => (shouldDebounce ? debounce(editPreferences, debounceInterval) : editPreferences),
        [debounceInterval],
    );

    const dataIsReady = !!schema?.data && !!preferences?.data;

    if (dataIsReady) {
        return (
            <Formik<INotificationPreferences>
                initialValues={preferences.data!}
                onSubmit={async function (values, { resetForm }) {
                    try {
                        await possiblyDebouncedEditPreferences(values, {
                            onSuccess: () =>
                                toast.addToast({
                                    autoDismiss: true,
                                    body: <>{t("Success! Your changes were saved.")}</>,
                                }),
                            onError: (e) => {
                                toast.addToast({
                                    dismissible: true,
                                    body: <>{t(e.message)}</>,
                                });
                            },
                        });
                    } catch (e) {
                        resetForm();
                    }
                }}
                enableReinitialize
            >
                {({ values }) => {
                    return (
                        <form onSubmit={(e) => e.preventDefault()} aria-label={t("Notification Preferences")}>
                            <JsonSchemaForm
                                schema={schema.data! as JsonSchema}
                                FormGroupWrapper={(props) => {
                                    const isTopLevelGroup = !!schema.data!.properties[props.groupName!];
                                    return <>{isTopLevelGroup ? <TopLevelGroup {...props} /> : props.children}</>;
                                }}
                                instance={utils.mapNotificationPreferencesToSchemaLikeStructure(
                                    schema.data! as JsonSchema,
                                    values,
                                )}
                                FormSection={(props) => {
                                    const isParentOfNotificationPreferenceSchemas =
                                        utils.isParentOfNotificationPreferenceSchemas(props.schema);

                                    const isSubGroup = props.path.length >= 2;

                                    return (
                                        <>
                                            {isParentOfNotificationPreferenceSchemas ? (
                                                <ConditionalWrap
                                                    condition={isSubGroup}
                                                    component={SubGroup}
                                                    componentProps={props}
                                                >
                                                    <TableFormSection {...props} />
                                                </ConditionalWrap>
                                            ) : (
                                                props.children
                                            )}
                                        </>
                                    );
                                }}
                                onChange={() => {}}
                            />
                        </form>
                    );
                }}
            </Formik>
        );
    } else {
        return (
            <>
                <ScreenReaderContent tag="span">{t("Loading")}</ScreenReaderContent>
                <SkeletonTopLevelGroup>
                    <SkeletonSubGroupWithTable />
                    <SkeletonSubGroupWithTable />
                    <SkeletonSubGroupWithTable />
                </SkeletonTopLevelGroup>
            </>
        );
    }
}

const TopLevelGroup: NonNullable<React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"]> = (props) => {
    const formClasses = notificationPreferencesFormClasses();
    const { header, description } = props;
    return (
        <PageBox
            options={{
                borderType: BorderType.SEPARATOR_BETWEEN,
            }}
        >
            {!!header && <Heading depth={2} title={header} />}
            {!!description && (
                <p className={formClasses.description} dangerouslySetInnerHTML={{ __html: `${description}` }} />
            )}
            {props.children}
        </PageBox>
    );
};

const SubGroup: NonNullable<React.ComponentProps<typeof JsonSchemaForm>["FormSection"]> = (props) => {
    const formClasses = notificationPreferencesFormClasses();

    const { title, description } = props;

    return (
        <PageBox
            options={{
                borderType: BorderType.NONE,
            }}
            className={formClasses.subgroupWrapper}
        >
            {!!title && <Heading depth={3} title={title} className={formClasses.subgroupHeading} />}
            {!!description && (
                <p className={formClasses.description} dangerouslySetInnerHTML={{ __html: `${description}` }} />
            )}
            {props.children}
        </PageBox>
    );
};

const TableFormSection: NonNullable<React.ComponentProps<typeof JsonSchemaForm>["FormSection"]> = (props) => {
    const { instance, schema } = props;

    const { submitForm, setFieldValue } = useFormikContext<INotificationPreferences>();

    const formClasses = notificationPreferencesFormClasses();

    const shouldRenderPopupColumn = Object.values(instance).some((value: object) => "popup" in value);
    const shouldRenderEmailColumn = Object.values(instance).some((value: object) => "email" in value);

    const columns = useMemo<Array<Column<ColumnType>>>(() => {
        let columns: Array<Column<ColumnType>> = [];

        if (shouldRenderPopupColumn) {
            columns.push({
                accessor: "popup",
                Header: function PopupColumnHeader() {
                    return (
                        <ToolTip label={t("Notification popup")}>
                            <span>
                                <Icon size="default" icon={"me-notifications"} />
                            </span>
                        </ToolTip>
                    );
                },
                Cell: function RenderCell(cellProps) {
                    if (cellProps.cell.value === undefined) {
                        return null;
                    }
                    return (
                        <Checkbox
                            checked={cellProps.cell.value}
                            className={formClasses.checkbox}
                            onChange={async function (event) {
                                setFieldValue(cellProps.row.id, {
                                    ...instance[cellProps.row.id],
                                    popup: event.target.checked,
                                });
                                await submitForm();
                            }}
                            label={t("Notification popup")}
                            hideLabel
                            aria-describedby={makeRowDescriptionId(cellProps.row)}
                        />
                    );
                },
            });
        }
        if (shouldRenderEmailColumn) {
            columns.push({
                accessor: "email",
                Header: function EmailColumnHeader() {
                    return (
                        <ToolTip label={t("Email")}>
                            <span>
                                <Icon size="default" icon={"me-inbox"} />
                            </span>
                        </ToolTip>
                    );
                },
                Cell: function RenderCell(cellProps) {
                    if (cellProps.cell.value === undefined) {
                        return null;
                    }
                    return (
                        <Checkbox
                            checked={cellProps.cell.value}
                            className={formClasses.checkbox}
                            onChange={async function (event) {
                                setFieldValue(cellProps.row.id, {
                                    ...instance[cellProps.row.id],
                                    email: event.target.checked,
                                });
                                await submitForm();
                            }}
                            label={t("Email")}
                            hideLabel
                            aria-describedby={makeRowDescriptionId(cellProps.row)}
                        />
                    );
                },
            });
        }

        columns.push({
            accessor: "description",
            Cell: function RenderDescriptionCell(cellProps) {
                return (
                    <span id={makeRowDescriptionId(cellProps.row)} className={formClasses.tableDescriptionWrapper()}>
                        {cellProps.cell.value}
                    </span>
                );
            },
        });

        return columns;
    }, [instance]);

    const data = useMemo(() => {
        return Object.entries(instance).map(([key, val]) => {
            return {
                ...(val as { email: boolean; popup: boolean }),
                id: key,
                description: `${schema.properties?.[key]?.["x-control"]?.["description"] ?? key}`,
            };
        });
    }, [instance]);

    const table: TableType = useTable({
        data,
        columns,
        getRowId: (row) => row.id,
    });

    return <PreferencesTable table={table} />;
};

function SkeletonTopLevelGroup(props: PropsWithChildren<{}>) {
    const formClasses = notificationPreferencesFormClasses();

    return (
        <PageBox
            options={{
                borderType: BorderType.SEPARATOR_BETWEEN,
            }}
        >
            <Heading depth={2}>
                <LoadingRectangle width={200} />
            </Heading>

            <Paragraph className={formClasses.description}>
                <LoadingRectangle width={300} />
            </Paragraph>
            {props.children}
        </PageBox>
    );
}

function SkeletonSubGroupWithTable() {
    const formClasses = notificationPreferencesFormClasses();

    function SkeletonPopupColumnHeader() {
        return <LoadingRectangle width={20} height={20} />;
    }

    function SkeletonRenderCheckboxCell() {
        return <LoadingRectangle width={20} height={20} />;
    }

    function SkeletonRenderDescriptionCell() {
        const minWidth = 200;
        const width = Math.floor((Math.random() + 1) * minWidth);
        return (
            <span className={formClasses.tableDescriptionWrapper()}>
                <LoadingRectangle width={width} />
            </span>
        );
    }

    const columns = useMemo<Array<Column<ColumnType>>>(() => {
        return [
            {
                id: "popup",
                Header: SkeletonPopupColumnHeader,
                Cell: SkeletonRenderCheckboxCell,
            },
            {
                id: "email",
                Header: SkeletonPopupColumnHeader,
                Cell: SkeletonRenderCheckboxCell,
            },
            {
                id: "description",
                Cell: SkeletonRenderDescriptionCell,
            },
        ];
    }, []);

    const skeletonRow = { popup: true, email: true, description: "", id: "" };
    const data = useMemo(() => {
        return [skeletonRow, skeletonRow];
    }, []);

    const table: TableType = useTable({
        data,
        columns,
    });

    return (
        <PageBox
            options={{
                borderType: BorderType.NONE,
            }}
            className={formClasses.subgroupWrapper}
        >
            <Heading depth={3} className={formClasses.subgroupHeading}>
                <LoadingRectangle width={100} />
            </Heading>
            <PreferencesTable table={table} />
        </PageBox>
    );
}
