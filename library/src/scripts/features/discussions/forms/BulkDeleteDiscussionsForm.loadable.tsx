/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css, cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { IBulkActionForm } from "@library/features/discussions/BulkActionsModal";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { useBulkDelete, useDiscussionByIDs } from "@library/features/discussions/discussionHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@library/utility/appUtils";
import { RecordID } from "@vanilla/utils";
import React, { useEffect, useMemo, useState } from "react";

/**
 * Displays the bulk delete form
 * @deprecated Do not import this component, import BulkDeleteDiscussions instead
 */
export default function BulkDeleteDiscussionsForm(props: IBulkActionForm) {
    const { onCancel } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const errorMessageOffset = css({
        marginBottom: globalVariables().spacer.size,
    });

    const { checkedDiscussionIDs } = useDiscussionCheckBoxContext();

    // Caching IDs because checkedDiscussionIDs will change during deletion process
    const [cachedActionIDs, setCachedIDs] = useState<RecordID[]>([]);
    const { isDeletePending, deletionFailedIDs, deletionSuccessIDs, deleteSelectedIDs } =
        useBulkDelete(cachedActionIDs);
    const failedDiscussions = useDiscussionByIDs(deletionFailedIDs ?? []);

    const errorMessage = useMemo(() => {
        return failedDiscussions
            ? `${t("There was a problem deleting:")} ${Object.values(failedDiscussions)
                  .map(({ name }) => `"${name}"`)
                  .join(", ")}`
            : null;
    }, [failedDiscussions]);

    const isSuccess = useMemo(
        () => (deletionSuccessIDs && deletionSuccessIDs.length > 0) ?? false,
        [deletionSuccessIDs],
    );

    useEffect(() => {
        setCachedIDs(checkedDiscussionIDs);
    }, []);

    return (
        <form>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Delete")} />}
                body={
                    <FrameBody>
                        <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                            {errorMessage && (
                                <Message
                                    className={errorMessageOffset}
                                    icon={<ErrorIcon />}
                                    stringContents={errorMessage}
                                />
                            )}
                            {isSuccess ? (
                                <Translate source={"Selected discussions have been deleted successfully."} />
                            ) : (
                                <Translate
                                    source={"Are you sure you would like to delete <0/> discussions?"}
                                    c0={cachedActionIDs.length}
                                />
                            )}
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={onCancel}
                            className={classFrameFooter.actionButton}
                        >
                            {isSuccess ? t("Close") : t("Cancel")}
                        </Button>
                        {!isSuccess && (
                            <Button
                                disabled={isDeletePending}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={classFrameFooter.actionButton}
                                onClick={() => deleteSelectedIDs()}
                            >
                                {isDeletePending ? <ButtonLoader /> : t("Delete")}
                            </Button>
                        )}
                    </FrameFooter>
                }
            />
        </form>
    );
}
