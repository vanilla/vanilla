/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import ReportModal from "@vanilla/addon-vanilla/thread/ReportModal";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import { useState } from "react";

interface IProps {
    discussionName: string;
    recordType: "discussion" | "comment";
    recordID: RecordID;
    placeRecordType: string;
    placeRecordID: RecordID;
    customTrigger?: CustomTriggerRender;
    onSuccess?: () => Promise<void>;
    initialVisibility?: boolean;
    isLegacyPage?: boolean;
}

export interface CustomTriggerProps {
    onClick: () => void;
}

type CustomTriggerRender = (props: CustomTriggerProps) => React.ReactNode;

export function ReportRecordOption(props: IProps) {
    const [isVisible, setIsVisible] = useState(props.initialVisibility ?? false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    async function handleSuccess() {
        props.onSuccess && (await props.onSuccess());
        close();
    }

    return (
        <>
            {props.customTrigger ? (
                props.customTrigger({ onClick: open })
            ) : (
                <DropDownItemButton onClick={open}>{t("Report")}</DropDownItemButton>
            )}
            <ReportModal
                discussionName={props.discussionName}
                recordID={props.recordID}
                recordType={props.recordType}
                placeRecordID={props.placeRecordID}
                placeRecordType={props.placeRecordType}
                isVisible={isVisible}
                onVisibilityChange={() => close()}
                onSuccess={handleSuccess}
                isLegacyPage={props.isLegacyPage}
            />
        </>
    );
}
