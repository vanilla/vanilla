/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { Table } from "@dashboard/components/Table";
import { TableAccordion } from "@dashboard/components/TableAccordion";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import ProfileFieldsListClasses from "@dashboard/userProfiles/components/ProfileFieldsList.classes";
import { css, cx } from "@emotion/css";
import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import DateTime, { DateFormats } from "@library/content/DateTime";
import UserContent from "@library/content/UserContent";
import { codeMixin } from "@library/content/UserContent.styles";
import { IError } from "@library/errorPages/CoreErrorMessages";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { FormControlGroup, FormControlWithNewDropdown } from "@library/forms/FormControl";
import InputBlock from "@library/forms/InputBlock";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import Message from "@library/messages/Message";
import { MetaButton, MetaItem, Metas, MetaTag } from "@library/metas/Metas";
import { TokenItem } from "@library/metas/TokenItem";
import ProfileLink from "@library/navigation/ProfileLink";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { dateRangeToString } from "@library/search/SearchUtils";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useMeasure } from "@vanilla/react-utils";
import { labelize, RecordID } from "@vanilla/utils";
import { useCallback, useMemo, useRef, useState } from "react";

export function AuditLogsPage() {
    const [expandedAuditLogs, setExpandedAuditLogs] = useState<Record<string, boolean>>({});
    const [page, setPage] = useState(useQueryParamPage());
    const DEFAULT_FORM: IAuditLogForm = {
        insertUserID: [],
        eventType: [],
        insertIPAddress: null,
        onlySpoofedActions: false,
        dateInserted: {
            start: "",
            end: "",
        },
    };
    const initialForm: IAuditLogForm = {
        insertUserID: useQueryParam("insertUserID", []),
        eventType: useQueryParam("eventType", []),
        insertIPAddress: useQueryParam("insertIPAddress", null),
        onlySpoofedActions: useQueryParam("onlySpoofedActions", false),
        dateInserted: useQueryParam("dateInserted", DEFAULT_FORM.dateInserted),
    };
    const [form, _setForm] = useState<IAuditLogForm>({
        ...initialForm,
        insertUserID: Array.isArray(initialForm.insertUserID)
            ? (initialForm.insertUserID ?? []).flat()
            : (Object.values(initialForm.insertUserID).flat() as RecordID[]),
    });
    useQueryStringSync({ ...form, page }, { ...DEFAULT_FORM, page: 1 });
    const setForm = useCallback(
        (newForm: Partial<IAuditLogForm> | ((existingForm: IAuditLogForm) => IAuditLogForm)) => {
            if (typeof newForm === "function") {
                _setForm(newForm);
            } else {
                _setForm((existing) => ({ ...existing, ...newForm }));
            }
            setPage(1);
        },
        [_setForm],
    );
    const auditLogQuery = useAuditLogsQuery(form, page);

    const paginationProps: INumberedPagerProps = {
        totalResults: auditLogQuery.data?.pagination?.total,
        currentPage: auditLogQuery.data?.pagination?.currentPage,
        pageLimit: auditLogQuery.data?.pagination?.limit,
        hasMorePages: auditLogQuery.data?.pagination?.total ? auditLogQuery.data?.pagination?.total >= 10000 : false,
    };

    const tableWrapRef = useRef<HTMLDivElement>(null);
    const tableMeasure = useMeasure(tableWrapRef);
    const mainColumnSize = tableMeasure.width > 0 ? tableMeasure.width - 240 : 500;

    const rows = useMemo(() => {
        return (
            auditLogQuery.data?.results.map((item) => {
                const isExpanded = !!expandedAuditLogs[item.auditLogID];
                let url = item.requestPath;
                const params = new URLSearchParams(item.requestQuery).toString();
                const fullUrl = url + `?${params}`;
                return {
                    action: (
                        <div
                            style={{
                                // maxWidth: 500,
                                maxWidth: mainColumnSize,
                                // width: "100%",
                                // overflow: "hidden",
                            }}
                        >
                            <TableAccordion
                                toggleButtonContent={
                                    <>
                                        <div>
                                            <div>
                                                <FormattedMessage message={item.message} />
                                            </div>
                                            <Metas>
                                                <MetaTag>{item.eventType}</MetaTag>
                                                <MetaItem className={classes.urlMeta}>
                                                    <code className={cx("codeInline", "code")}>
                                                        <span>{item.requestMethod}</span>{" "}
                                                        <span title={fullUrl}>{url}</span>
                                                    </code>
                                                </MetaItem>
                                            </Metas>
                                        </div>
                                    </>
                                }
                            >
                                <AuditContextSummary auditLog={item} />
                            </TableAccordion>
                        </div>
                    ),
                    member: (
                        <span style={{ display: "flex", alignItems: "center", gap: 8 }}>
                            <span className={classes.memberCell}>
                                {item.orcUserEmail != null ? (
                                    <>
                                        <a href={`mailto:${item.orcUserEmail}`}>{item.orcUserEmail}</a>{" "}
                                        {t("spoofed as")}{" "}
                                    </>
                                ) : (
                                    item.spoofUserID != null &&
                                    item.spoofUserID !== item.insertUserID && (
                                        <>
                                            <ProfileLink
                                                userFragment={{
                                                    ...item.spoofUser,
                                                    userID: item.spoofUserID,
                                                }}
                                            />{" "}
                                            {t("spoofed as")}{" "}
                                        </>
                                    )
                                )}
                                <ProfileLink
                                    userFragment={{
                                        ...item.insertUser,
                                        userID: item.insertUserID,
                                        name: item.insertUserID === 0 ? t("Guest") : item.insertUser?.name,
                                    }}
                                    isUserCard
                                />
                                <Metas>
                                    <MetaButton
                                        onClick={() => {
                                            setForm({ insertIPAddress: item.insertIPAddress });
                                        }}
                                    >
                                        {item.insertIPAddress}
                                    </MetaButton>
                                </Metas>
                            </span>
                        </span>
                    ),

                    date: (
                        <div className={classes.dateCell}>
                            <DateTime mode="fixed" type={DateFormats.EXTENDED} timestamp={item.dateInserted} />
                        </div>
                    ),
                };
            }) ?? []
        );
    }, [auditLogQuery.data, setForm, mainColumnSize]);

    return (
        <div>
            <DashboardHeaderBlock
                title={t("Audit Logs")}
                actionButtons={
                    <NumberedPager showNextButton={false} onChange={setPage} isMobile={false} {...paginationProps} />
                }
            />
            <div ref={tableWrapRef}>
                {auditLogQuery.isLoading && <div>{t("Loading...")}</div>}
                {auditLogQuery.error && <Message error={auditLogQuery.error} />}
                {auditLogQuery.data && (
                    <div className={dashboardClasses().extendRow} key={"data"}>
                        <Table
                            truncateCells={false}
                            headerClassNames={ProfileFieldsListClasses().dashboardHeaderStyles}
                            rowClassNames={ProfileFieldsListClasses().extendTableRows}
                            data={rows}
                        />
                    </div>
                )}
            </div>
            <DashboardHelpAsset>
                <PageHeadingBox depth={3} title={t("Filters")} />
                <JsonSchemaForm
                    schema={schema}
                    FormControl={FormControlWithNewDropdown}
                    FormControlGroup={FormControlGroup}
                    instance={form}
                    onChange={setForm}
                />
                {form.insertIPAddress && (
                    <InputBlock label={"Member IP Address"}>
                        <TokenItem
                            onRemove={() => {
                                setForm({ insertIPAddress: null });
                            }}
                        >
                            {form.insertIPAddress}
                        </TokenItem>
                    </InputBlock>
                )}
                <Button
                    buttonType={ButtonTypes.STANDARD}
                    onClick={() => {
                        setForm(DEFAULT_FORM);
                    }}
                >
                    Reset Filters
                </Button>
            </DashboardHelpAsset>
        </div>
    );
}

const schema: JsonSchema = {
    type: "object",
    properties: {
        insertUserID: {
            type: "array",
            items: {
                type: "integer",
            },
            "x-control": {
                inputType: "tokens",
                label: t("Member"),
                choices: {
                    api: {
                        labelKey: "name",
                        valueKey: "userID",
                        searchUrl: "/api/v2/users/by-names?name=%s*&order=dateLastActive",
                        singleUrl: "/api/v2/users/%s",
                    },
                },
            },
        },
        eventType: {
            type: "array",
            items: {
                type: "string",
            },
            "x-control": {
                inputType: "dropDown",
                multiple: true,
                label: t("Action"),
                choices: {
                    api: {
                        labelKey: "name",
                        valueKey: "eventType",
                        searchUrl: "/api/v2/audit-logs/event-types",
                        singleUrl: "",
                        extraLabelKey: "eventType",
                    },
                },
            },
        },
        dateInserted: {
            type: "object",
            nullable: true,
            "x-control": {
                label: t("Event Date"),
                inputType: "dateRange",
            },
            properties: {
                start: {
                    nullable: true,
                    type: "string",
                },
                end: {
                    nullable: true,
                    type: "string",
                },
            },
        },
        onlySpoofedActions: {
            type: "boolean",
            "x-control": {
                label: t("Only Spoofed Actions"),
                inputType: "checkBox",
            },
        },
    },
};

const classes = {
    memberCell: css({}),
    urlMeta: css({
        ...codeMixin(),
    }),
    dateCell: css({ display: "flex", width: "100%" }),
    summaryTitle: css({
        fontWeight: 600,
        fontSize: 12,
    }),
    message: css({
        fontWeight: 600,
    }),
    contextRow: css({
        display: "flex",
        gap: 4,
        flexWrap: "wrap",
        alignItems: "center",
    }),
};

interface IModification {
    old: any;
    new: any;
}
interface IAuditLog {
    auditLogID: string;
    message: string;
    messageHtml: string;
    eventType: string;
    requestMethod: string;
    requestPath: string;
    requestQuery: Record<string, string>;
    insertUserID: number;
    insertUser?: IUserFragment;
    insertIPAddress: string;
    dateInserted: string;
    context: {
        modifications?: Record<string, IModification>;
        [key: string]: any;
    };
    childEvents?: IAuditLog[];
    spoofUserID?: number | null;
    spoofUser?: IUserFragment | null;
    orcUserEmail?: string | null;
}

interface IAuditLogForm {
    insertUserID: RecordID[];
    eventType: string[];
    insertIPAddress: string | null;
    onlySpoofedActions: boolean;
    dateInserted: {
        start: string;
        end: string;
    };
}

interface IAuditLogsQueryData {
    results: IAuditLog[];
    pagination: ILinkPages;
}

function useAuditLogsQuery(formValues: IAuditLogForm, page: number) {
    const query = useQuery<any, IError, IAuditLogsQueryData>({
        queryKey: ["audit-logs", "filters", formValues, page],
        queryFn: async () => {
            const query: any = { ...formValues, expand: "users", limit: 30, page };
            if (!query.insertIPAddress) {
                delete query.insertIPAddress;
            }

            if (formValues.dateInserted.end || formValues.dateInserted.start) {
                query.dateInserted = dateRangeToString(formValues.dateInserted);
            } else {
                delete query.dateInserted;
            }

            const response = await apiv2.get("/audit-logs", {
                params: query,
            });
            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return { results: response.data ?? [], pagination };
        },
        keepPreviousData: true,
        retry: false,
    });

    return query;
}

function AuditContextSummary(props: { auditLog: IAuditLog }) {
    const { context, requestQuery } = props.auditLog;

    const { modifications, ...rest } = context;

    const hasQuery = Object.keys(requestQuery).length > 0;
    const hasContext = Object.keys(context).length > 0;
    if (!hasContext && !hasQuery) {
        return <div className={classes.contextRow}>Nothing to see here!</div>;
    }

    if (hasQuery) {
        rest["requestQuery"] = requestQuery;
    }

    return (
        <div>
            {modifications && <ModificationSummary modifications={modifications} />}
            {Object.entries(rest).map(([key, value]) => {
                if (key === "message") {
                    return;
                }
                return (
                    <div key={key}>
                        <div>
                            <strong className={classes.summaryTitle}>{labelize(key)}</strong>
                        </div>
                        <div className={classes.contextRow}>
                            <FormattedContextValue value={value} />
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function ModificationSummary(props: { modifications: Record<string, IModification> }) {
    const { modifications } = props;
    return (
        <div>
            {Object.entries(modifications).map(([key, value]) => {
                return (
                    <div key={key}>
                        <div>
                            <strong className={classes.summaryTitle}>{labelize(key)}</strong>
                        </div>
                        <p className={classes.contextRow}>
                            <span>From</span>
                            <FormattedContextValue value={value.old} />
                            <span>To</span>
                            <FormattedContextValue value={value.new} />
                        </p>
                    </div>
                );
            })}
        </div>
    );
}

function FormattedContextValue(props: { value: any }) {
    const { value } = props;
    if (typeof value === "string" || typeof value === "number") {
        return <TokenItem>{value}</TokenItem>;
    }

    if (value == null) {
        return <TokenItem>null</TokenItem>;
    }

    if (typeof value === "boolean") {
        return <TokenItem>{value ? "true" : "false"}</TokenItem>;
    }

    if (Array.isArray(value)) {
        return (
            <>
                {value.map((item, index) => {
                    return <FormattedContextValue key={index} value={item} />;
                })}
            </>
        );
    }

    if (typeof value === "object") {
        return (
            <UserContent
                className={css({ fontSize: 12, maxWidth: "100%", width: "100%", overflow: "hidden", marginTop: 2 })}
                content={`<pre><code class="code codeBlock">${JSON.stringify(value, null, 4)}</code></pre>`}
            />
        );
    }
    return <TokenItem>{value}</TokenItem>;
}

function FormattedMessage(props: { message: string }) {
    const { message } = props;
    const pieces = message.split("`");

    return (
        <strong className={cx(classes.contextRow, classes.message)}>
            {pieces.map((piece, i) => {
                const isCode = i % 2 === 1;
                if (piece === "") {
                    return;
                }
                if (isCode) {
                    return (
                        <TokenItem compact key={i}>
                            {piece}
                        </TokenItem>
                    );
                } else {
                    return <span key={i}>{piece}</span>;
                }
            })}
        </strong>
    );
}
