/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import { IBulkActionForm } from "@library/features/discussions/BulkActionsModal";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { useBulkDiscussionMove, useDiscussionByIDs } from "@library/features/discussions/discussionHooks";
import { bulkDiscussionsClasses } from "@library/features/discussions/forms/BulkDiscussions.classes";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Checkbox from "@library/forms/Checkbox";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import { cx } from "@library/styles/styleShim";
import { t } from "@library/utility/appUtils";
import { CategoryDisplayAs } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { RecordID } from "@vanilla/utils";
import React, { useEffect, useMemo, useState } from "react";

/**
 * Displays the bulk move form
 * @deprecated Do not import this component, import BulkMoveDiscussions instead
 */
export default function BulkMoveDiscussionsForm(props: IBulkActionForm) {
    const { onCancel } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = bulkDiscussionsClasses();
    const { checkedDiscussionIDs } = useDiscussionCheckBoxContext();

    // Caching IDs because checkedDiscussionIDs will change during deletion process
    const [cachedActionIDs, setCachedIDs] = useState<RecordID[]>([]);
    // State for the combobox (the category which discussions will be moved to)
    const [moveTarget, setMoveTarget] = useState<IComboBoxOption[] | null>();
    // State for the checkbox (if a redirect link should be left in moved discussions)
    const [addRedirects, setAddRedirects] = useState<boolean>(false);

    // This state needs to hold the category ID to be used in the hook below
    // so that we can fetch the category early to update the discussion list
    // upon successful move
    const targetCategoryID = moveTarget?.[0]?.value;

    const { isSuccess, isPending, failedDiscussions, moveSelectedDiscussions } = useBulkDiscussionMove(
        cachedActionIDs,
        targetCategoryID,
        addRedirects,
    );

    // Generate an error message listing all the failed discussion
    const errorMessage = useMemo<string | null>(() => {
        return failedDiscussions
            ? `${t("There was a problem moving:")} ${Object.values(failedDiscussions)
                  .map(({ name }) => `"${name}"`)
                  .join(", ")}`
            : null;
    }, [failedDiscussions]);

    // Cache IDs on load incase they change during operation
    useEffect(() => {
        setCachedIDs(checkedDiscussionIDs);
    }, []);

    // Reset the redirect option if target discussion is cleared
    useEffect(() => {
        if (!moveTarget || !moveTarget.length) {
            setAddRedirects(false);
        }
    }, [moveTarget]);

    const handleBulkMove = () => {
        moveSelectedDiscussions();
    };

    return (
        <form>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Move")} />}
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
                                <Translate source={"Selected discussions have been moved successfully."} />
                            ) : (
                                <>
                                    <Translate
                                        source={"Are you sure you would like to move <0/> discussions?"}
                                        c0={cachedActionIDs.length}
                                    />
                                    <CommunityCategoryInput
                                        displayAs={CategoryDisplayAs.DISCUSSIONS}
                                        placeholder={t("Select a category")}
                                        label={""}
                                        onChange={(option) => {
                                            setMoveTarget(() => (option[0] ? option : null));
                                        }}
                                        value={moveTarget ?? []}
                                        maxHeight={80}
                                    />
                                    <div className={classes.separatedSection}>
                                        <Checkbox
                                            className={classes.checkboxLabel}
                                            label={t("Leave a redirect link")}
                                            disabled={!moveTarget || !moveTarget.length}
                                            checked={addRedirects}
                                            onChange={(e) => setAddRedirects(e.target.checked)}
                                        />
                                    </div>
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
                                disabled={isPending || !moveTarget || !moveTarget.length}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={classFrameFooter.actionButton}
                                onClick={() => handleBulkMove()}
                            >
                                {isPending ? <ButtonLoader /> : t("Move")}
                            </Button>
                        )}
                    </FrameFooter>
                }
            />
        </form>
    );
}
