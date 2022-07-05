import { ReactElement } from "react";
import { mountPortal } from "@vanilla/react-utils";

export const MODAL_CONTAINER_ID = "modals";
export const PAGE_CONTAINER_ID = "page";
/**
 * Mount a modal from a top level context.
 *
 * If you are already in a react context, just use `<Modal />`.
 */

export function mountModal(node: ReactElement<any>) {
    return mountPortal(node, MODAL_CONTAINER_ID);
}
