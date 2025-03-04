/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@library/forms/InputTextBlock";
import { ErrorIcon } from "@library/icons/common";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import Message from "@library/messages/Message";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { ToolTip } from "@library/toolTip/ToolTip";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useState } from "react";

export default function GenerateDataExportURL() {
    const [isVisible, setIsVisible] = useState(false);

    const toast = useToast();

    const { data, isLoading, error } = useQuery<any, IApiError, { status: string; exportUrl: string }>({
        queryFn: async () => {
            const response = await apiv2.get("/churn-export/export-url");
            return response.data;
        },
        enabled: isVisible,
        queryKey: ["generateDataExportURL"],
        staleTime: 0,
    });
    return (
        <>
            <Button
                buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                onClick={() => setIsVisible(true)}
                className={css({ marginTop: 16 })}
            >
                {t("Generate Data Export URL")}
            </Button>
            <ModalConfirm
                isVisible={isVisible}
                size={ModalSizes.MEDIUM}
                title={t("Data Export URL")}
                onCancel={() => setIsVisible(false)}
                cancelTitle={t("Close")}
                isConfirmLoading={isLoading}
                fullWidthContent
            >
                {error && <Message type="error" stringContents={error.message} icon={<ErrorIcon />} />}
                {isLoading && (
                    <div>
                        <LoadingRectangle height={30} width={350} />
                    </div>
                )}
                {data?.exportUrl && (
                    <div
                        className={css({
                            display: "flex",
                            justifyContent: "space-between",
                            alignItems: "center",
                            "& > *:first-child": { marginRight: 8 },
                        })}
                    >
                        <InputTextBlock
                            inputProps={{
                                value: data?.exportUrl,
                                disabled: true,
                            }}
                        />
                        <ToolTip label={t("Copy URL")} customWidth={40}>
                            <Button
                                title={t("Copy URL")}
                                aria-label={t("Copy URL")}
                                buttonType={ButtonTypes.ICON_COMPACT}
                                onClick={async () => {
                                    await navigator.clipboard.writeText(data?.exportUrl);
                                    toast.addToast({
                                        body: <>{t("URL copied to clipboard.")}</>,
                                        autoDismiss: true,
                                    });
                                }}
                            >
                                <Icon icon={"copy-link"} />
                            </Button>
                        </ToolTip>
                    </div>
                )}
            </ModalConfirm>
        </>
    );
}
