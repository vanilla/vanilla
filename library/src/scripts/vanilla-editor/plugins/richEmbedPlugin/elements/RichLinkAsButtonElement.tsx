/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ButtonType } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { IVanillaLinkAsButtonElement } from "@library/vanilla-editor/typescript";
import { getNodeString, PlateRenderElementProps } from "@udecode/plate-common";

interface IProps extends PlateRenderElementProps<any, IVanillaLinkAsButtonElement> {}

/**
 * Responsible for rendering a link as a button when you convert an embed to a button with the menu.
 */
export function RichLinkAsButtonElement(props: IProps) {
    const { attributes, children, nodeProps, element } = props;

    const textContent = getNodeString(element);

    return (
        <span contentEditable={false} suppressContentEditableWarning={true} data-testid="link-as-button">
            <SmartLink
                className={element.buttonType === ButtonType.PRIMARY ? "Button Primary" : "Button"}
                role="button"
                to={element.url}
                {...nodeProps}
                {...attributes}
                tabIndex={-1}
                aria-label={textContent}
                onClick={(e) => {
                    e.preventDefault();
                }}
            >
                {children}
                {textContent}
            </SmartLink>
        </span>
    );
}
