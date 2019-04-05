/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/* tslint:disable:max-classes-per-file */
declare module "react-aria-live" {
    export class LiveAnnouncer extends React.Component<{ children: React.ReactNode }> {}
    export class LiveMessage extends React.Component<{
        message: string;
        "aria-live": "polite" | "assertive" | "off";
        clearOnUnmount: boolean;
    }> {}
    export class LiveMessenger extends React.Component<{ children: React.ReactNode }> {}
}
