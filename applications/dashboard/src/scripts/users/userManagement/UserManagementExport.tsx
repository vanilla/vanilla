/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import { useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import {
    mapColumnNameToSelectEntry,
    UserManagementColumnNames,
} from "@dashboard/users/userManagement/UserManagementUtils";
import apiv2 from "@library/apiv2";
import NumberFormatted from "@library/content/NumberFormatted";
import Translate from "@library/content/Translate";
import { IToast, useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { MetaItem, Metas } from "@library/metas/Metas";
import ModalConfirm from "@library/modal/ModalConfirm";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { downloadAsFile } from "@vanilla/dom-utils";
import { t } from "@vanilla/i18n";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { logError } from "@vanilla/utils";
import { AxiosResponse } from "axios";
import moment from "moment";
import React, { useRef, useState } from "react";

export function useUsersExport(columns: string[], downloader: typeof downloadAsFile = downloadAsFile) {
    const [isFetching, setIsFetching] = useState(false);
    const [showCancel, setShowCancel] = useState(false);
    const toastContext = useToast();
    const { profileFields } = useUserManagement();
    const cancelRef = useRef<boolean>(false);
    useRouteChangePrompt(
        t(
            "Leaving the page will cause your user export to be cancelled. Existing progress will be lost. Are you sure you want to cancel the user export?",
        ),
        !isFetching,
    );

    const exportUsers = async (usersQuery: IGetUsersQueryParams) => {
        setIsFetching(true);
        cancelRef.current = false;

        async function fetchUsersCsv(): Promise<AxiosResponse> {
            const fieldMapping = Object.fromEntries(
                [...columns, UserManagementColumnNames.USER_ID].flatMap((column) =>
                    // Technically these might not be loaded yet, but in reality we are showing a loading page and all of that will be loaded before this code gets hit.
                    // In the worst case if someone SOMEHOW manages to trigger the start of this JS process
                    // Before things are loaded then they will get an export without profile fields.
                    //
                    // Additionally because they are captured into this closure they will be consistent
                    // for the whole duration of the download, which is important so the exported CSV is consistent.
                    mapColumnNameToSelectEntry(column, profileFields ?? []),
                ),
            );
            const response = await apiv2.get<string>("/users.csv", {
                params: {
                    ...usersQuery,
                    limit: 1000,
                    expand: "all",
                    sort: "-userID",
                    fieldMapping,
                },
            });
            return response;
        }

        let nextRequest = () => fetchUsersCsv();
        let csvResult = "";

        let toastID: string | null = null;
        let countFetched = 0;
        let isFirst = true;
        let initialTotal: number | null = null;
        try {
            while (nextRequest !== null) {
                if (cancelRef.current) {
                    if (toastID) {
                        toastContext.removeToast(toastID);
                    }
                    return;
                }

                const response = await nextRequest();
                const { nextURL, total } = SimplePagerModel.parseHeaders(response.headers);
                if (total !== null && initialTotal === null) {
                    initialTotal = total ?? null;
                }
                let newCsv: string = response.data;

                // The number of users we fetched is based off of the number of lines we read.
                // There is always an extra newline because of the headings.
                countFetched += (newCsv.match(/\n/g) || "").length - 1;

                if (!isFirst) {
                    // If we are not the very first fetch, we should be stripping off the CSV header.
                    const firstNewLineIndex = newCsv.indexOf("\n");
                    newCsv = newCsv.slice(firstNewLineIndex + 1);
                }

                csvResult += newCsv;
                if (nextURL) {
                    isFirst = false;
                    nextRequest = () => apiv2.get(nextURL);
                } else {
                    const toaster = {
                        wide: false,
                        dismissible: true,
                        body: (
                            <div>
                                <strong>{t("User Export Complete")}</strong>
                                <p>{t("Successfully downloaded user export.")}</p>
                            </div>
                        ),
                    };
                    if (toastID) {
                        toastContext.updateToast(toastID, toaster);
                    } else {
                        toastContext.addToast(toaster);
                    }
                    break;
                }

                const toastContent: IToast = {
                    persistent: true,
                    wide: true,
                    body: (
                        <UserExportToast
                            onCancel={() => {
                                setShowCancel(true);
                            }}
                            countTotal={initialTotal ?? 0}
                            countFetched={countFetched}
                        />
                    ),
                };
                if (toastID) {
                    toastContext.updateToast(toastID, toastContent);
                } else {
                    toastID = toastContext.addToast(toastContent);
                }
            }
        } catch (err) {
            logError(err);
            if (toastID) {
                toastContext.removeToast(toastID);
            }
            toastContext.addToast({
                dismissible: true,
                body: (
                    <div>
                        <strong>{t("User Export Failed")}</strong>
                        <p>
                            <ErrorMessages errors={[err]} />
                        </p>
                    </div>
                ),
            });
        } finally {
            setShowCancel(false);
            setIsFetching(false);
        }

        // Do this so we can more easily mock the time.
        const date = new Date(Date.now());
        downloader(csvResult, `user-export_${moment(date).format("YYYY-MM-DD_HH-mm-ss")}`);
    };

    const cancelDialogue = showCancel ? (
        <ModalConfirm
            onCancel={() => {
                setShowCancel(false);
            }}
            onConfirm={() => {
                cancelRef.current = true;
            }}
            title={"Cancel User Export?"}
            confirmTitle={t("Cancel Export")}
            cancelTitle={t("Dismiss")}
            isVisible
        >
            {t("Are you sure you want to cancel the user export? Existing progress will be lost.")}
        </ModalConfirm>
    ) : (
        <></>
    );

    return { exportUsers, isFetching, cancelDialogue };
}

interface IProps {
    countTotal: number;
    countFetched: number;
    onCancel?: () => void;
}

export function UserExportToast(props: IProps) {
    const classes = userManagementClasses();
    const total = (
        <>
            <NumberFormatted showFullValue value={props.countTotal} />
            {props.countTotal >= 10000 ? "+" : ""}
        </>
    );
    const fetched = <NumberFormatted showFullValue value={props.countFetched} />;
    return (
        <div className={classes.exportToast}>
            <span>
                <ButtonLoader />
            </span>
            <div className={classes.exportToastContent}>
                <strong>{t("Preparing to export user data")}</strong>
                <p>
                    {props.countFetched < props.countTotal && props.countTotal > 0 ? (
                        <Translate source="<0 /> of <1 /> users prepared." c0={fetched} c1={total} />
                    ) : (
                        <Translate source="<0 /> users prepared." c0={fetched} />
                    )}
                </p>
                <Metas>
                    <MetaItem>{t("This may take a few minutes.")}</MetaItem>
                </Metas>
            </div>
            {props.onCancel && (
                <Button onClick={props.onCancel} buttonType={ButtonTypes.TEXT}>
                    {t("Cancel")}
                </Button>
            )}
        </div>
    );
}
