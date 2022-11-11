/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { LoadStatus } from "@library/@types/api/core";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useDiscussionActions } from "@library/features/discussions/DiscussionActions";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { useDiscussionByIDs, useDiscussionList } from "@library/features/discussions/discussionHooks";
import { BulkDiscussionErrors } from "@library/features/discussions/forms/BulkDiscussionErrors";
import { bulkDiscussionsClasses } from "@library/features/discussions/forms/BulkDiscussions.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Checkbox from "@library/forms/Checkbox";
import ErrorMessages from "@library/forms/ErrorMessages";
import RadioButton from "@library/forms/RadioButton";
import RadioButtonGroup from "@library/forms/RadioButtonGroup";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { getLoadingPercentageForIndex, LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { ILongRunnerResponse } from "@library/LongRunnerClient";
import Message from "@library/messages/Message";
import { useLongRunnerAction } from "@library/useLongRunner";
import { t } from "@vanilla/i18n";
import { AutoComplete, AutoCompleteOption, FormGroup, FormGroupInput, FormGroupLabel } from "@vanilla/ui";
import { notEmpty, RecordID } from "@vanilla/utils";
import { count } from "console";
import React, { useEffect, useMemo, useState } from "react";

interface IMergeRequestBody {
    discussionIDs: Array<IDiscussion["discussionID"]>;
    destinationDiscussionID: IDiscussion["discussionID"];
    addRedirects?: boolean;
}

interface IProps {
    onCancel: () => void;
}

export default function DiscussionMergeFormImpl(props: IProps) {
    const { checkedDiscussionIDs, addPendingDiscussionByIDs, removePendingDiscussionByIDs } =
        useDiscussionCheckBoxContext();

    const discussionActions = useDiscussionActions();

    // Keep these stable even as we are removing them.
    const initialCheckedIDs = useMemo(() => {
        return checkedDiscussionIDs;
    }, []);

    const [destinationDiscussionID, setDestinationDiscussionID] = useState<RecordID | null>(null);
    const [addRedirects, setAddRedirects] = useState<boolean>(false);
    const [exceptionsByDiscussionID, setExceptionsByDiscussionID] = useState<
        ILongRunnerResponse["progress"]["exceptionsByID"] | null
    >(null);

    const [mergeState, mergeDiscussions] = useLongRunnerAction<IMergeRequestBody>("PATCH", "/discussions/merge", {
        success: (successIDs) => {
            removePendingDiscussionByIDs(successIDs);
            const refetchIDs = successIDs;
            if (destinationDiscussionID) {
                refetchIDs.push(destinationDiscussionID as number);
            }
            discussionActions.getDiscussionByIDs({ discussionIDs: refetchIDs, expand: "all" }, true);
        },
        failed: (failedIDs, exceptionsByID) => {
            removePendingDiscussionByIDs(failedIDs);
            setExceptionsByDiscussionID(exceptionsByID);
        },
    });
    const isSuccess = mergeState.status === "success";
    const isLoading = mergeState.status === "loading";
    const canSubmit = mergeState.status === "pending" && destinationDiscussionID !== null;
    const classes = bulkDiscussionsClasses();
    const hasErrors = exceptionsByDiscussionID != null || mergeState.error != null;
    useEffect(() => {
        if (!hasErrors && isSuccess) {
            props.onCancel();
        }
    }, [hasErrors, isSuccess, props.onCancel]);

    return (
        <form>
            <Frame
                header={<FrameHeader title={t("Merge Discussions")} closeFrame={props.onCancel} />}
                body={
                    <FrameBody hasVerticalPadding>
                        <BulkDiscussionErrors
                            errorsByDiscussionID={exceptionsByDiscussionID}
                            generalError={mergeState.error}
                            messagePrefix={t("Failed to merge discussions:")}
                        />
                        <DiscussionSelector
                            discussionIDs={initialCheckedIDs}
                            value={destinationDiscussionID}
                            onChange={setDestinationDiscussionID}
                            maxRadioInputs={10}
                        />
                        <div className={classes.separatedSection}>
                            <Checkbox
                                label={t("Leave a redirect link")}
                                checked={addRedirects}
                                onChange={(e) => setAddRedirects(e.target.checked)}
                            />
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button buttonType={ButtonTypes.TEXT} onClick={props.onCancel}>
                            {isSuccess ? t("Close") : t("Cancel")}
                        </Button>
                        {!isSuccess && (
                            <Button
                                disabled={!canSubmit}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                onClick={async () => {
                                    addPendingDiscussionByIDs(initialCheckedIDs);
                                    await mergeDiscussions({
                                        discussionIDs: initialCheckedIDs,
                                        destinationDiscussionID: destinationDiscussionID!,
                                        addRedirects,
                                    });
                                    removePendingDiscussionByIDs(initialCheckedIDs);
                                }}
                            >
                                {isLoading ? <ButtonLoader /> : t("Merge")}
                            </Button>
                        )}
                    </FrameFooter>
                }
            />
        </form>
    );
}

interface IDiscussionSelectorProps {
    discussionIDs: Array<IDiscussion["discussionID"]>;
    value: RecordID | null;
    onChange: (value: RecordID) => void;
    maxRadioInputs: number;
}

function DiscussionSelector(props: IDiscussionSelectorProps) {
    const discussions = useDiscussionList({ discussionID: props.discussionIDs, limit: props.discussionIDs.length });

    if (discussions.status === LoadStatus.ERROR || !discussions.data?.discussionList) {
        return (
            <Message icon={<ErrorIcon />} stringContents={discussions.error?.message ?? t("An error has occured.")} />
        );
    }

    const classes = bulkDiscussionsClasses();

    if (props.discussionIDs.length > props.maxRadioInputs) {
        return (
            <FormGroup>
                <FormGroupLabel>
                    {t("Choose the main discussion into which all comments will be merged:")}
                </FormGroupLabel>
                <FormGroupInput>
                    {(labelProps) => {
                        return (
                            <AutoComplete
                                {...labelProps}
                                size={"default"}
                                inputClassName={classes.autocomplete}
                                value={props.value}
                                clear
                                placeholder={t("Select a discussion")}
                                onChange={(value) => {
                                    props.onChange(value);
                                }}
                                options={discussions.data?.discussionList.map((discussion) => {
                                    return {
                                        label: discussion.name,
                                        value: discussion.discussionID,
                                    };
                                })}
                            />
                        );
                    }}
                </FormGroupInput>
            </FormGroup>
        );
    }

    return (
        <RadioButtonGroup label={t("Choose the main discussion into which all comments will be merged:")}>
            {discussions.status === LoadStatus.PENDING || discussions.status === LoadStatus.LOADING ? (
                <RadioButtonGroup>
                    {props.discussionIDs.map((discussionID, i) => {
                        return (
                            <RadioButton
                                disabled={true}
                                checked={false}
                                key={i}
                                label={<LoadingRectangle width={getLoadingPercentageForIndex(i)} height={16} />}
                            />
                        );
                    })}
                </RadioButtonGroup>
            ) : (
                discussions.data.discussionList.map((discussion, i) => {
                    return (
                        <RadioButton
                            key={i}
                            checked={discussion.discussionID === props.value}
                            onChecked={() => {
                                props.onChange(discussion.discussionID);
                            }}
                            label={discussion.name}
                        />
                    );
                })
            )}
        </RadioButtonGroup>
    );
}
