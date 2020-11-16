import { RouteComponentProps } from "react-router-dom";
import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import {
    useRoleRequestMetasList,
    useRoleRequestsList,
    useRoleRequestsState,
} from "@dashboard/roleRequests/state/roleRequestHooks";
import {
    BasicAttributes,
    BasicType,
    ILoadableWithCount,
    IRoleRequest,
    IRoleRequestMeta,
    RoleRequestStatus,
    RoleRequestType,
} from "@dashboard/roleRequests/state/roleRequestTypes";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import Button from "@library/forms/Button";
import { ApproveIcon, ErrorIcon, PendingIcon } from "@library/icons/common";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DateTime from "@library/content/DateTime";
import FilterDropDown from "@library/dataLists/FilterDropDown";
import { forceInt, labelize } from "@vanilla/utils";
import { DashboardPagerArea, DashboardToolbar, DashboardToolbarButtons } from "@dashboard/components/DashboardToolbar";
import QueryString from "@library/routing/QueryString";
import { LoadStatus } from "@library/@types/api/core";
import { useRoleRequestActions } from "@dashboard/roleRequests/state/roleRequestActions";
import { DashboardPager } from "@dashboard/components/DashboardPager";
import { PageLoadStatus } from "@library/loaders/PageLoadStatus";

interface IProps extends RouteComponentProps {}

export default function RoleApplicationsPage(props: IProps) {
    return (
        <>
            <DashboardHeaderBlock title={t("Role Applicants")} />
            <RoleApplicationsBody {...props} />
        </>
    );
}

type StatusButtonOnClick = (roleRequestID: number, status: RoleRequestStatus) => void;

// The number of items to display in the queue.
const PAGE_DISPLAY_LIMIT = 10;
// The number of items to request each limit.
const PAGE_LIMIT = 30;
// The number of items left in the queue to trigger a reload.
const PAGE_RELOAD_LIMIT = 15;

const DEFAULT_PARAMS = {
    type: RoleRequestType.APPLICATION,
    roleID: undefined,
    status: RoleRequestStatus.PENDING,
    page: 1,
    limit: PAGE_LIMIT,
};

function RoleApplicationsBody(props: IProps) {
    const query = new URLSearchParams(props.location.search);
    const [roleID, setRoleIDFilter] = useState(query.get("roleID") ?? "");
    const [status, setStatusFilter] = useState(query.get("status") ?? RoleRequestStatus.PENDING);
    const [page, setPage] = useState(forceInt(query.get("page"), 1));

    const params = {
        type: DEFAULT_PARAMS.type,
        roleID: roleID === "" ? DEFAULT_PARAMS.roleID : forceInt(roleID, 0),
        status: status as RoleRequestStatus,
        offset: (page - 1) * PAGE_DISPLAY_LIMIT,
        limit: PAGE_LIMIT,
    };
    const sort = params.status === RoleRequestStatus.PENDING ? "dateInserted" : "-dateOfStatus";
    const roleRequests = useRoleRequestsList({ ...params, sort }, PAGE_RELOAD_LIMIT);
    const metas = useRoleRequestMetasList({ type: RoleRequestType.APPLICATION });

    if (!metas.data) {
        return <PageLoadStatus loadable={metas}>...</PageLoadStatus>;
    }

    const metaOptions = Object.values<IRoleRequestMeta>(metas.data).map((meta) => ({
        value: meta.roleID.toString(),
        name: meta.role.name,
    }));
    metaOptions.unshift({ value: "", name: t("All") });

    const statusOptions = [
        { value: RoleRequestStatus.PENDING, name: t("Pending") },
        { value: RoleRequestStatus.APPROVED, name: t("Approved") },
        { value: RoleRequestStatus.DENIED, name: t("Denied") },
    ];

    return (
        <>
            <QueryString value={{ ...params, offset: undefined, page }} defaults={DEFAULT_PARAMS} />
            <DashboardToolbar>
                <DashboardToolbarButtons>
                    <FilterDropDown
                        id="statusFilter"
                        label={t("Role Request Status", "Status")}
                        options={statusOptions}
                        onChange={(s) => {
                            setStatusFilter(s);
                            setPage(1);
                        }}
                        value={status}
                    />
                    <FilterDropDown
                        id="roleFilter"
                        label={t("Role")}
                        options={metaOptions}
                        onChange={(id) => {
                            setRoleIDFilter(id);
                            setPage(1);
                        }}
                        value={roleID}
                    />
                </DashboardToolbarButtons>
                {roleRequests && (
                    <DashboardPagerArea>
                        <DashboardPager
                            page={page}
                            hasNext={hasNextPage(roleRequests)}
                            onClick={(newPage) => setPage(newPage)}
                            disabled={!roleRequests.data}
                        />
                    </DashboardPagerArea>
                )}
            </DashboardToolbar>
            <PageLoadStatus loadable={roleRequests}>
                <RoleRequestTable roleRequests={roleRequests.data!} metas={metas.data} />
            </PageLoadStatus>
        </>
    );
}

function hasNextPage(data: ILoadableWithCount<any>): boolean {
    if (!data.count || !data.data) {
        return false;
    } else if (data.count! <= PAGE_LIMIT && data.data.length <= PAGE_DISPLAY_LIMIT) {
        return false;
    }
    return true;
}

export function RoleRequestTable({
    roleRequests,
    metas,
}: {
    roleRequests: IRoleRequest[];
    metas: Record<number, IRoleRequestMeta>;
}) {
    const { HeadItem } = DashboardTable;

    return (
        <DashboardTable
            verticalAlign={true}
            head={
                <tr>
                    <HeadItem>{t("User")}</HeadItem>
                    <HeadItem>{t("Role")}</HeadItem>
                    <HeadItem size={TableColumnSize.XS}>{t("Date")}</HeadItem>
                    <HeadItem>{t("Attributes")}</HeadItem>
                    <HeadItem size={TableColumnSize.XS}>{t("Actions")}</HeadItem>
                </tr>
            }
            body={roleRequests.slice(0, PAGE_DISPLAY_LIMIT).map((request: IRoleRequest) => (
                <tr key={request.userID}>
                    <td>
                        <a href={request.user?.url}>{request.user?.name}</a>
                    </td>
                    <td>{request.role?.name || t("Unknown")}</td>
                    <td>
                        <DateTime timestamp={request.dateInserted} />
                    </td>
                    <td>
                        <BasicSchemaAttributes attributes={request.attributes} meta={metas[request.roleID] ?? null} />
                    </td>
                    <td>
                        <DashboardTableOptions>
                            <RoleRequestButtons roleRequest={request} />
                        </DashboardTableOptions>
                    </td>
                </tr>
            ))}
        />
    );
}

function RoleRequestButtons({ roleRequest, onClick }: { roleRequest: IRoleRequest; onClick?: StatusButtonOnClick }) {
    const { patchRoleRequest } = useRoleRequestActions();
    // Look up a patching status.
    const patching = useRoleRequestsState().roleRequestPatchingByID[roleRequest.roleRequestID];
    const disabled = patching && patching.status === LoadStatus.LOADING;

    if (roleRequest.status === RoleRequestStatus.APPROVED) {
        return null;
    }

    const handleClick = (status: RoleRequestStatus) => {
        patchRoleRequest({ roleRequestID: roleRequest.roleRequestID, status });
        if (onClick) {
            onClick(roleRequest.roleRequestID, status);
        }
    };

    return (
        <>
            <Button
                className="btn-icon"
                baseClass={ButtonTypes.ICON}
                title={t("Approve")}
                disabled={disabled}
                onClick={() => handleClick(RoleRequestStatus.APPROVED)}
            >
                <ApproveIcon />
            </Button>
            {roleRequest.status === RoleRequestStatus.PENDING && (
                <Button
                    className="btn-icon"
                    baseClass={ButtonTypes.ICON_COMPACT}
                    title={t("Deny")}
                    disabled={disabled}
                    onClick={() => handleClick(RoleRequestStatus.DENIED)}
                >
                    <ErrorIcon />
                </Button>
            )}
            {roleRequest.status === RoleRequestStatus.DENIED && (
                <Button
                    className="btn-icon"
                    baseClass={ButtonTypes.ICON_COMPACT}
                    title={t("Mark Pending")}
                    disabled={disabled}
                    onClick={() => handleClick(RoleRequestStatus.PENDING)}
                >
                    <PendingIcon />
                </Button>
            )}
        </>
    );
}

function BasicSchemaAttributes({ attributes, meta }: { attributes: BasicAttributes; meta: IRoleRequestMeta }) {
    return (
        <dl>
            {Object.entries(attributes).map(([key, value]) => {
                const metaProperty = meta.attributesSchema?.properties[key];
                let label = key;
                if (metaProperty) {
                    label = metaProperty["x-label"] ?? label;
                }
                label = labelize(label);

                return (
                    <React.Fragment key={key}>
                        <dt>{label}</dt>
                        <dd>
                            <BasicSchemaValue value={value} />
                        </dd>
                    </React.Fragment>
                );
            })}
        </dl>
    );
}

function BasicSchemaValue({ value }: { value: BasicType }) {
    let str;
    switch (typeof value) {
        case "boolean":
            str = value ? t("yes") : t("no");
            break;
        case "object":
            str = value === null ? "null" : "object";
            break;
        default:
            str = value.toString();
    }
    return <>{str}</>;
}
