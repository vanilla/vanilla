/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ManageIconModal } from "@dashboard/appearance/manageIcons/ManageIconModal";
import type { ManageIconsApi } from "@dashboard/appearance/manageIcons/ManageIconsApi";
import { ManageIconsBulkActions } from "@dashboard/appearance/manageIcons/ManageIconsBulkActions";
import { ManageIconsForm, type IManageIconsForm } from "@dashboard/appearance/manageIcons/ManageIconsForm";
import { ManageIconsFormContextProvider } from "@dashboard/appearance/manageIcons/ManageIconsFormContext";
import { ManageIconsTable } from "@dashboard/appearance/manageIcons/ManageIconsTable";
import { useMockedApi } from "@library/__tests__/utility";
import { STORY_USER } from "@library/storybook/storyData";
import { useState } from "react";

export default {
    title: "Admin/Manage Icons",
};

export function Table() {
    return <ManageIconsTable activeIcons={MOCK_MANAGED_ICONS} />;
}

export function FormWithTable() {
    const [formValues, setFormValues] = useState<IManageIconsForm>({
        iconSize: "96px",
        iconFilter: "data-",
        iconColor: "#FF00AA",
        iconType: "all",
    });
    return (
        <div>
            <div style={{ padding: 16 }}>
                <ManageIconsForm value={formValues} onChange={setFormValues}></ManageIconsForm>
            </div>
            <ManageIconsFormContextProvider {...formValues}>
                <ManageIconsTable activeIcons={MOCK_MANAGED_ICONS} />
            </ManageIconsFormContextProvider>
        </div>
    );
}

export function Modal() {
    useMockedApi((mock) => {
        mock.onGet("/icons/by-name").reply(200, MOCK_MANAGED_ICONS_REVISIONS);
    });

    return <ManageIconModal activeIcon={MOCK_MANAGED_ICONS[0]} onClose={() => {}}></ManageIconModal>;
}

export function BulkActions() {
    useMockedApi((mock) => {
        mock.onGet("/icons/active")
            .reply(200, MOCK_MANAGED_ICONS)
            .onGet("/icons/system")
            .reply(200, MOCK_MANAGED_ICONS);
    });

    return <ManageIconsBulkActions forceOpen={true} />;
}

const MOCK_MANAGED_ICONS: ManageIconsApi.IManagedIcon[] = [
    {
        iconUUID: "add",
        isCustom: false,
        iconName: "add",
        svgRaw: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">\n    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 4C11.4477 4 11 4.44772 11 5V11H5C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13H11V19C11 19.5523 11.4477 20 12 20C12.5523 20 13 19.5523 13 19V13H19C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11H13V5C13 4.44772 12.5523 4 12 4Z" fill="#555A62"/>\n</svg>\n',
        svgContents:
            '<path fill-rule="evenodd" clip-rule="evenodd" d="M12 4C11.4477 4 11 4.44772 11 5V11H5C4.44772 11 4 11.4477 4 12C4 12.5523 4.44772 13 5 13H11V19C11 19.5523 11.4477 20 12 20C12.5523 20 13 19.5523 13 19V13H19C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11H13V5C13 4.44772 12.5523 4 12 4Z" fill="currentColor"></path>',
        svgAttributes: {
            width: "24",
            height: "24",
            fill: "none",
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
    {
        iconUUID: "meta-article",
        isCustom: false,
        iconName: "meta-article",
        svgRaw: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">\n    <path fill-rule="evenodd" clip-rule="evenodd" d="M5 4C4.44772 4 4 4.44772 4 5V19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V5C20 4.44772 19.5523 4 19 4H5ZM5 19V5H19V19H5ZM12 7H7V12H12V7ZM8 11V8H11V11H8ZM14 15H7V16H14V15ZM7 13H17V14H7V13ZM17 9H13V10H17V9ZM13 11H17V12H13V11ZM17 7H13V8H17V7Z" fill="#555A62"/>\n</svg>\n',
        svgContents:
            '<path fill-rule="evenodd" clip-rule="evenodd" d="M5 4C4.44772 4 4 4.44772 4 5V19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V5C20 4.44772 19.5523 4 19 4H5ZM5 19V5H19V19H5ZM12 7H7V12H12V7ZM8 11V8H11V11H8ZM14 15H7V16H14V15ZM7 13H17V14H7V13ZM17 9H13V10H17V9ZM13 11H17V12H13V11ZM17 7H13V8H17V7Z" fill="currentColor"></path>',
        svgAttributes: {
            width: "24",
            height: "24",
            fill: "none",
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
    {
        iconUUID: "data-checked",
        isCustom: false,
        iconName: "data-checked",
        svgRaw: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">\n    <polygon fill="currentColor" points="5,12.7 3.6,14.1 9,19.5 20.4,7.9 19,6.5 9,16.8" />\n</svg>\n',
        svgContents: '<polygon fill="currentColor" points="5,12.7 3.6,14.1 9,19.5 20.4,7.9 19,6.5 9,16.8"></polygon>',
        svgAttributes: {
            width: "24",
            height: "24",
            fill: "none",
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
    {
        iconUUID: "data-down",
        isCustom: false,
        iconName: "data-down",
        svgRaw: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">\n<path d="M12.7 4C12.7 3.6134 12.3866 3.3 12 3.3C11.6134 3.3 11.3 3.6134 11.3 4H12.7ZM11.505 20.495C11.7784 20.7683 12.2216 20.7683 12.495 20.495L16.9497 16.0402C17.2231 15.7668 17.2231 15.3236 16.9497 15.0503C16.6764 14.7769 16.2332 14.7769 15.9598 15.0503L12 19.0101L8.0402 15.0503C7.76684 14.7769 7.32362 14.7769 7.05025 15.0503C6.77689 15.3236 6.77689 15.7668 7.05025 16.0402L11.505 20.495ZM11.3 4L11.3 20H12.7L12.7 4H11.3Z" fill="#555A62"/>\n</svg>\n',
        svgContents:
            '<path d="M12.7 4C12.7 3.6134 12.3866 3.3 12 3.3C11.6134 3.3 11.3 3.6134 11.3 4H12.7ZM11.505 20.495C11.7784 20.7683 12.2216 20.7683 12.495 20.495L16.9497 16.0402C17.2231 15.7668 17.2231 15.3236 16.9497 15.0503C16.6764 14.7769 16.2332 14.7769 15.9598 15.0503L12 19.0101L8.0402 15.0503C7.76684 14.7769 7.32362 14.7769 7.05025 15.0503C6.77689 15.3236 6.77689 15.7668 7.05025 16.0402L11.505 20.495ZM11.3 4L11.3 20H12.7L12.7 4H11.3Z" fill="currentColor"></path>',
        svgAttributes: {
            width: "24",
            height: "24",
            fill: "none",
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
    {
        iconUUID: "data-drag-and-drop",
        isCustom: false,
        iconName: "data-drag-and-drop",
        svgRaw: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">\n<path d="M12.6255 11.3788H16.2458V9.99914C16.2458 9.66504 16.6497 9.49773 16.8859 9.73398L19.8906 11.7386C20.037 11.8851 20.037 12.1225 19.8906 12.269L16.8859 14.2736C16.6497 14.5099 16.2458 14.3426 16.2458 14.0085V12.6288H12.6254V16.2468H14.0051C14.3392 16.2468 14.5065 16.6508 14.2703 16.887L12.2656 19.8917C12.1192 20.0381 11.8818 20.0381 11.7353 19.8917L9.73063 16.887C9.49438 16.6508 9.66169 16.2469 9.99578 16.2469H11.3755V12.6288H7.75513V14.0085C7.75513 14.3426 7.35119 14.5099 7.11497 14.2736L4.11032 12.269C3.96388 12.1225 3.96388 11.8851 4.11032 11.7386L7.11497 9.73398C7.35122 9.49773 7.75513 9.66504 7.75513 9.99914V11.3788H11.3755V7.75617H9.99582C9.66172 7.75617 9.49441 7.35223 9.73066 7.11601L11.7353 4.11135C11.8818 3.96492 12.1192 3.96492 12.2657 4.11135L14.2703 7.11601C14.5066 7.35226 14.3393 7.75617 14.0052 7.75617H12.6255V11.3788Z" fill="#555A62"/>\n</svg>\n',
        svgContents:
            '<path d="M12.6255 11.3788H16.2458V9.99914C16.2458 9.66504 16.6497 9.49773 16.8859 9.73398L19.8906 11.7386C20.037 11.8851 20.037 12.1225 19.8906 12.269L16.8859 14.2736C16.6497 14.5099 16.2458 14.3426 16.2458 14.0085V12.6288H12.6254V16.2468H14.0051C14.3392 16.2468 14.5065 16.6508 14.2703 16.887L12.2656 19.8917C12.1192 20.0381 11.8818 20.0381 11.7353 19.8917L9.73063 16.887C9.49438 16.6508 9.66169 16.2469 9.99578 16.2469H11.3755V12.6288H7.75513V14.0085C7.75513 14.3426 7.35119 14.5099 7.11497 14.2736L4.11032 12.269C3.96388 12.1225 3.96388 11.8851 4.11032 11.7386L7.11497 9.73398C7.35122 9.49773 7.75513 9.66504 7.75513 9.99914V11.3788H11.3755V7.75617H9.99582C9.66172 7.75617 9.49441 7.35223 9.73066 7.11601L11.7353 4.11135C11.8818 3.96492 12.1192 3.96492 12.2657 4.11135L14.2703 7.11601C14.5066 7.35226 14.3393 7.75617 14.0052 7.75617H12.6255V11.3788Z" fill="currentColor"></path>',
        svgAttributes: {
            width: "24",
            height: "24",
            fill: "none",
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
    {
        iconUUID: "data-folder-tabs",
        isCustom: false,
        iconName: "data-folder-tabs",
        svgRaw: '<svg\n    xmlns="http://www.w3.org/2000/svg"\n    width="24"\n    height="24"\n    viewBox="0 0 24 24"\n>\n    <g\n        transform="translate(-12 -136)"\n        fill="none"\n        fillRule="evenodd"\n    >\n        <g transform="translate(12 136)">\n            <path\n                fill="currentColor"\n                d="M5.5 19h13a1.5 1.5 0 001.5-1.5V9a1.5 1.5 0 00-1.5-1.5H12L10.222 5H5.5A1.5 1.5 0 004 6.5v11A1.5 1.5 0 005.5 19z"\n            />\n        </g>\n        <path stroke="currentColor" d="M22.5 142h4l1 2v1h-5z" />\n        <path stroke="currentColor" d="M26.5 142h4l1 2v1h-3.75z" />\n    </g>\n</svg>\n',
        svgContents:
            '<g transform="translate(-12 -136)" fill="none" fillrule="evenodd"><g transform="translate(12 136)"><path fill="currentColor" d="M5.5 19h13a1.5 1.5 0 001.5-1.5V9a1.5 1.5 0 00-1.5-1.5H12L10.222 5H5.5A1.5 1.5 0 004 6.5v11A1.5 1.5 0 005.5 19z"></path></g><path stroke="currentColor" d="M22.5 142h4l1 2v1h-5z"></path><path stroke="currentColor" d="M26.5 142h4l1 2v1h-3.75z"></path></g>',
        svgAttributes: {
            xmlns: "http://www.w3.org/2000/svg",
            width: "24",
            height: "24",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
    {
        iconUUID: "data-information",
        isCustom: false,
        iconName: "data-information",
        svgRaw: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">\n<circle cx="12" cy="12" r="7.5" stroke="currentColor"/>\n<rect x="11" y="10.5" width="2" height="6" fill="currentColor"/>\n<circle cx="12" cy="8.5" r="1" fill="currentColor"/>\n</svg>\n',
        svgContents:
            '<circle cx="12" cy="12" r="7.5" stroke="currentColor"></circle><rect x="11" y="10.5" width="2" height="6" fill="currentColor"></rect><circle cx="12" cy="8.5" r="1" fill="currentColor"></circle>',
        svgAttributes: {
            width: "24",
            height: "24",
            fill: "none",
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
        },
        insertUserID: 1,
        insertUser: STORY_USER,
        dateInserted: "2025-01-01T00:00:00Z",
        isActive: true,
    },
];

const MOCK_MANAGED_ICONS_REVISIONS: ManageIconsApi.IManagedIcon[] = MOCK_MANAGED_ICONS.map((icon) => ({
    ...icon,
    iconName: "mocked-icon",
    isActive: false,
}));

MOCK_MANAGED_ICONS_REVISIONS[0].isActive = true;
