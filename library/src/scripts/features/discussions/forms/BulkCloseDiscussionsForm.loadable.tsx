/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import { IBulkActionForm } from "@library/features/discussions/BulkActionsModal";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { useBulkDiscussionClose, useDiscussionByIDs } from "@library/features/discussions/discussionHooks";
import { bulkDiscussionsClasses } from "@library/features/discussions/forms/BulkDiscussions.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import Message from "@library/messages/Message";
import { cx } from "@library/styles/styleShim";
import { t } from "@library/utility/appUtils";
import { RecordID } from "@vanilla/utils";
import React, { useEffect, useMemo, useState } from "react";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";

/**
 * Displays the bulk close confirmation
 */
export default function BulkCloseDiscussionsForm(props: IBulkActionForm) {
    const { onCancel } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = bulkDiscussionsClasses();
    const { checkedDiscussionIDs } = useDiscussionCheckBoxContext();

    // Caching IDs because checkedDiscussionIDs will change during closing process
    const [cachedActionIDs, setCachedIDs] = useState<RecordID[]>([]);

    const { isSuccess, isPending, failedDiscussions, closeSelectedDiscussions } = useBulkDiscussionClose(
        cachedActionIDs,
        true,
    );

    const errorMessage = useMemo<string | null>(() => {
        return failedDiscussions
            ? `${t("There was a problem closing:")} ${Object.values(failedDiscussions)
                  .map(({ name }) => `"${name}"`)
                  .join(", ")}`
            : null;
    }, [failedDiscussions]);

    useEffect(() => {
        setCachedIDs(checkedDiscussionIDs);
    }, []);

    const handleBulkClose = (evt) => {
        closeSelectedDiscussions();
    };

    return (
        <form>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Close Discussions")} />}
                body={
                    <FrameBody>
                        <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                            {errorMessage && (
                                <Message
                                    className={classes.errorMessageOffset}
                                    icon={<ErrorIcon />}
                                    stringContents={errorMessage}
                                />
                            )}
                            {isSuccess ? (
                                <Translate
                                    source={"Selected discussions have been successfully closed to commenting."}
                                />
                            ) : (
                                <>
                                    <Translate
                                        source={"Are you sure you would like to close <0/> discussions to commenting?"}
                                        c0={cachedActionIDs.length}
                                    />
                                </>
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
                                disabled={isPending}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={classFrameFooter.actionButton}
                                onClick={handleBulkClose}
                            >
                                {isPending ? <ButtonLoader /> : t("Close Discussions")}
                            </Button>
                        )}
                    </FrameFooter>
                }
            />
        </form>
    );
}
