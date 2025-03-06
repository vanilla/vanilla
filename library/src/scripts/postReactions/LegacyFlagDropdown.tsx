/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import { useState } from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { postReactionsClasses } from "@library/postReactions/PostReactions.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import ReportModal from "@vanilla/addon-vanilla/reporting/ReportModal";

interface IProps {
    discussionName: string;
    recordType: "discussion" | "comment";
    recordID: RecordID;
    placeRecordType: string;
    placeRecordID: RecordID;
    userName: string;
    userID: number;
    onSuccess?: () => Promise<void>;
    extraFlagOptions?: string[];
}

export function LegacyFlagDropdown(props: IProps) {
    const { recordType, recordID, userName, userID, extraFlagOptions } = props;
    const [reportModalIsVisible, setReportModalIsVisible] = useState(false);
    const classes = postReactionsClasses();

    const hasOtherFlagOptions = extraFlagOptions?.length && extraFlagOptions?.length > 0;

    async function handleSuccess() {
        props.onSuccess && (await props.onSuccess());
        close();
    }

    // legacy stuff
    const buttonContents = (
        <a className="ReactButton" tabIndex={0} title="Flag" role="button">
            <span className="ReactSprite ReactFlag" />
            <span className="ReactLabel">{t("Flag")}</span>
        </a>
    );

    return (
        <>
            {hasOtherFlagOptions ? (
                <DropDown
                    flyoutType={FlyoutType.LIST}
                    buttonClassName={classes.legacyFlagDropdownButton}
                    buttonContents={buttonContents}
                    preventFocusOnVisible
                >
                    <DropDownItemButton onClick={() => setReportModalIsVisible(true)}>{t("Report")}</DropDownItemButton>
                    {LegacyFlagDropdown.extraFlagOptions.map((FlagOption, index) => (
                        <FlagOption
                            key={index}
                            recordID={recordID}
                            recordType={recordType}
                            userName={userName}
                            userID={userID}
                            isLegacyPage
                        />
                    ))}
                </DropDown>
            ) : (
                <Button buttonType={ButtonTypes.TEXT} onClick={() => setReportModalIsVisible(true)}>
                    {buttonContents}
                </Button>
            )}
            <ReportModal
                recordName={props.discussionName}
                recordID={props.recordID}
                recordType={props.recordType}
                placeRecordID={props.placeRecordID}
                placeRecordType={props.placeRecordType}
                isVisible={reportModalIsVisible}
                onVisibilityChange={() => setReportModalIsVisible(false)}
                onSuccess={handleSuccess}
                isLegacyPage
            />
        </>
    );
}

export interface IFlagOption {
    recordType?: "discussion" | "comment";
    recordID?: RecordID;
    userName?: string;
    userID?: number;
    comment?: IComment;
    discussion?: IDiscussion;
    onSuccess?: () => Promise<void>;
    isLegacyPage?: boolean;
}

/** Hold the extra flag options, e.g from plugins. */
LegacyFlagDropdown.extraFlagOptions = [] as Array<React.ComponentType<IFlagOption>>;

/**
 * Register an extra flag options, e.g from plugins.
 *
 * @param component The component to be registered.
 */
LegacyFlagDropdown.registerExtraFlagOption = (component: React.ComponentType<IFlagOption>) => {
    LegacyFlagDropdown.extraFlagOptions.push(component);
};
