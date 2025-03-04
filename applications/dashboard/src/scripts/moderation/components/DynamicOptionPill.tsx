/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dynamicOptionPillClasses } from "@dashboard/moderation/components/DynamicOptionPill.classes";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { Icon } from "@vanilla/icons";

interface IProps extends ISelectBoxItem {
    data?: any;
    isActive: boolean;
    onChange: (updatedValue: boolean) => void;
    removeOption?: () => void;
    // If an user profile should be displayed, provide the path to the userfragment in the data prop
    userIconPath?: string;
}

export function DynamicOptionPill(props: IProps) {
    const classes = dynamicOptionPillClasses(props.isActive);
    const icon = props?.data && props?.userIconPath ? props.data[`${props.userIconPath}`] : false;
    return (
        <span key={props.value} className={cx(classes.root, { checked: props.isActive })}>
            <label className={classes.label}>
                {icon && <UserPhoto className={classes.photo} size={UserPhotoSize.XSMALL} userInfo={icon} />}
                <span className={classes.name}>{props.name}</span>
                <input
                    className={cx(visibility().visuallyHidden, classes.input)}
                    type="checkbox"
                    checked={props.isActive}
                    onChange={(e) => props.onChange(e.target.checked)}
                />
            </label>
            <Button className={classes.removeButton} buttonType={ButtonTypes.CUSTOM} onClick={props.removeOption}>
                <Icon icon={"dismiss-compact"} size={"compact"} />
            </Button>
        </span>
    );
}
