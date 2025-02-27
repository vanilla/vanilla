/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { IBulkActionButton } from "@library/bulkActions/BulkActions.types";
import { bulkActionsClasses } from "@library/bulkActions/BulkActions.classes";
import capitalize from "lodash-es/capitalize";

interface IProps {
    /** Function to clear all selected records */
    handleSelectionClear?(): void;
    /** Toast message when there are selected records  */
    selectionMessage: React.ReactNode;
    /** Necessary data to render bulk action buttons */
    bulkActionsButtons: IBulkActionButton[];
}

/**
 * This is the toast notification which is displayed when multiple records are selected
 *
 */
export function BulkActionsToast(props: IProps) {
    const { handleSelectionClear, bulkActionsButtons, selectionMessage } = props;
    const classes = bulkActionsClasses();

    return (
        <>
            <span className={classes.bulkActionsText}>{selectionMessage}</span>
            <div className={classes.bulkActionsButtons}>
                <Button onClick={handleSelectionClear} buttonType={ButtonTypes.TEXT}>
                    {t("Cancel")}
                </Button>
                {bulkActionsButtons.map((button, index) => {
                    return (
                        <ConditionalWrap
                            condition={button.notAllowed}
                            component={ToolTip}
                            componentProps={{
                                label: button.notAllowedMessage,
                            }}
                            key={index}
                        >
                            <span>
                                <Button
                                    onClick={button.handler}
                                    buttonType={ButtonTypes.TEXT}
                                    disabled={button.notAllowed}
                                >
                                    {t(capitalize(button.action))}
                                </Button>
                            </span>
                        </ConditionalWrap>
                    );
                })}
            </div>
        </>
    );
}
