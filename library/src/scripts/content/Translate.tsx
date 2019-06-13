/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { logError } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";

type TranslateCallback = (contents: string) => React.ReactNode;

interface IProps {
    source: string;
    errorHandler?: (...values) => void;
    translateFunction?: (source: string) => string;
    c0?: TranslateCallback | React.ReactNode;
    c1?: TranslateCallback | React.ReactNode;
    c2?: TranslateCallback | React.ReactNode;
    c3?: TranslateCallback | React.ReactNode;
    c4?: TranslateCallback | React.ReactNode;
}

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
 * <Translation source="test" />
 *   -> translated text of "test"
 * <Translation source="Hello <0/>" />
 *   -> Error because c0 was not provided.
 * <Translation source="Hello <0/> world!" c0={<SomeComponent />}/>
 *   -> Hello <SomeComponent/> world!
 * <Translation source="Visit <0>our site</0> for help." c0={content => <a href="http://site.com">{content}</a>}/>"
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
        // First parse out the self closing elements.
        const tagRegex = /<([\d\ ]+)>(.*?)<\/\1>|<([\d\ ]+)\/>|([^<]+|<)/g;

        const result: React.ReactNode[] = [];
        this.translatedSource.replace(tagRegex, (match, childrenID, childrenMatch, standaloneID, textMatch, index) => {
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
    private getInterpolatedComponent(id: string, key: number, value?: string): React.ReactNode {
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
        return this.props.translateFunction!(this.props.source);
    }

    /**
     * Log that an ID has not been found with some context.
     */
    private logIDNotFound(id) {
        this.props.errorHandler!(
            `A translation interpolation value #${id} was not provided for source string ${
                this.props.source
            }. \nThe translated value of source is ${this.translatedSource}.`,
        );
    }
}
