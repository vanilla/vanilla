/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { logError, notEmpty } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";
import { sprintf } from "sprintf-js";

type TranslateCallback = (contents: string) => React.ReactNode;

interface IProps {
    source: string;
    shortSource?: string;
    errorHandler?: (...values) => void;
    translateFunction?: (source: string, fallback?: string) => string;
    c0?: TranslateCallback | React.ReactNode;
    c1?: TranslateCallback | React.ReactNode;
    c2?: TranslateCallback | React.ReactNode;
    c3?: TranslateCallback | React.ReactNode;
    c4?: TranslateCallback | React.ReactNode;
}

const TAG_REGEX = /<([\d ]+)>(.*?)<\/\1>|<([\d ]+)\/>|([^<]+|<)/g;
const SPRINTF_PLACEHOLDER_REGEX = /%(?:\d+\$)?[dfsu]/g;

/**
 * Component for translating text with interpolated components.
 * Can accept source strings with interpolated translation components in a form such as:
 * - "Published on <0/> by <1 />."
 * - "For more information, please see our <0>public documentation</0>."
 *
 * About the placeholders:
 * - Self closing placeholders will be replace with the result from the prop that has a correspsonding ID.
 *   Eg. "<0/>" will be replaced by prop `c0`.
 *   "<3 />" will be replaced by prop `c3`.
 * - Placeholders content will have their translated content passed as an argument to their callback prop.
 *
 * Limitations
 * - Only placeholders 0-4 are currently allowed. Break up your translation strings!
 * - These tag's CANNOT be nested currently.
 *
 * Examples
 * <Translate source="test" />
 *   -> translated text of "test"
 * <Translate source="Hello <0/>" />
 *   -> Error because c0 was not provided.
 * <Translate source="Hello <0/> world!" c0={<SomeComponent />}/>
 *   -> Hello <SomeComponent/> world!
 * <Translate source="Visit <0>our site</0> for help." c0={content => <a href="http://site.com">{content}</a>}/>"
 *   -> Visit <a href="http://site.com">our site</a> for help.
 */
export default class Translate extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        errorHandler: logError,
        translateFunction: t,
    };

    /**
     * @inheritDoc
     */
    public render(): React.ReactNode {
        if (this.translatedSource.match(/<([\d ]+)\/?>/)) {
            return this.renderHtmlStubs();
        } else {
            return this.renderSprintf();
        }
    }

    /**
     * Render a translation string with sprintf.
     */
    public renderSprintf() {
        // Warn people if they us it incorrectly.
        const allArgs = [this.props.c0, this.props.c1, this.props.c2, this.props.c3, this.props.c4];
        allArgs.filter(notEmpty).forEach((arg) => {
            if (typeof arg === "function") {
                this.props.errorHandler!(
                    `Cannot use a functional translation interpolation value with a sprintf source string: ${this.props.source}. \nThe translated value of source is ${this.translatedSource}.`,
                );
            }
        });

        const translatedSource = this.translatedSource;
        const splitSource = translatedSource.split(SPRINTF_PLACEHOLDER_REGEX);
        const result: React.ReactNode[] = [];

        splitSource.forEach((textPiece, i) => {
            // Push in the text piece.
            result.push(<React.Fragment key={`text-${i}`}>{textPiece}</React.Fragment>);
            // Push in a value for the placeholder.
            const isLast = i === splitSource.length - 1;
            if (!isLast) {
                result.push(this.getInterpolatedComponent(i.toString(), `replaced-${i}`));
            }
        });

        return result;
    }

    /**
     * Render out an HTML placeholder translation string, like "<0>Login</0> or <1>Register</1>"
     */
    private renderHtmlStubs() {
        // First parse out the self closing elements.

        const result: React.ReactNode[] = [];
        this.translatedSource.replace(TAG_REGEX, (match, childrenID, childrenMatch, standaloneID, textMatch, index) => {
            if (textMatch != null) {
                result.push(<React.Fragment key={index}>{textMatch}</React.Fragment>);
            }

            if (standaloneID != null) {
                result.push(this.getInterpolatedComponent(standaloneID, index));
            }

            if (childrenMatch != null && childrenID != null) {
                result.push(this.getInterpolatedComponent(childrenID, index, childrenMatch));
            }
            return match;
        });

        return result;
    }

    /**
     * Create an interpolated component inside of a react fragment.
     *
     * @param id The id of the component we are replacing. Eg. <0/> -> 0.
     * @param key The key of the fragment (index).
     * @param value The content to pass along to the prop callback.
     */
    private getInterpolatedComponent(id: string, key: number | string, value?: string): React.ReactNode {
        id = id.trim();

        if (!(`c${id}` in this.props)) {
            this.logIDNotFound(id);
            return null;
        }

        const prop = this.props[`c${id}`];
        const contents = typeof prop === "function" ? prop(value) : prop;

        return <React.Fragment key={key}>{contents}</React.Fragment>;
    }

    /**
     * Get a translated version of the source string.
     */
    private get translatedSource(): string {
        return this.props.translateFunction!(this.props.shortSource ?? this.props.source, this.props.source);
    }

    /**
     * Log that an ID has not been found with some context.
     */
    private logIDNotFound(id) {
        this.props.errorHandler!(
            `A translation interpolation value #${id} was not provided for source string ${this.props.source}. \nThe translated value of source is ${this.translatedSource}.`,
        );
    }
}
