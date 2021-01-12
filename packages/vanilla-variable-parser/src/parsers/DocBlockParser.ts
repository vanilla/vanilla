/**
 * @copyright 2020 Adam (charrondev) Charron
 * @license Proprietary
 */

import { Validator, ValidatorFn } from "./Validator";

const GENERIC_AT_LINE = /\*\s+@/;
const GENERIC_TEXT_LINE = /\*\s+(?<text>.*)\s*/;

interface IAttribute<V = string> {
    attributeName: string;
    isMultiline: boolean;
    regexp: RegExp;
    validator: ValidatorFn<V>;
}

interface IAttributeMatch {
    attribute: IAttribute;
    leadingWhiteSpaceCount: number;
    value: string;
}

export interface IParseError {
    line: number;
    message: string;
}

interface IParseResult<T> {
    value: T;
    errors: IParseError[];
}

type DocBlockResult = Record<string, any>;

/**
 * Class for parsing docblocks into objects.
 *
 * @example
 * ```js
 * DocBlockParser
 *     .create()
 *     .setLeadingAttribute("my-thing")
 *     .setAttribute("my-thing-title")
 *     .setAttribute("my-thing-description", true);
 *
 * // Will parse
 * @my-thing
 * @my-thing-title Hello world
 * @my-thing-description Description here.
 * Line 2 of the description.
 * ```
 */
export class DocBlockParser<T extends DocBlockResult = DocBlockResult> {
    private leadingAttribute: IAttribute | null = null;
    private attributes: IAttribute[] = [];

    /**
     * Factory method.
     */
    public static create<T extends DocBlockResult = DocBlockResult>(): DocBlockParser<T> {
        return new DocBlockParser();
    }

    /**
     * Clone the current parser.
     */
    public clone<C extends DocBlockResult = T>(): DocBlockParser<C> {
        const clone = DocBlockParser.create<C>();
        clone.leadingAttribute = this.leadingAttribute;
        clone.attributes = this.attributes.slice();
        return clone;
    }

    /**
     * Set a leading attribute required for the parser.
     * This is a require attribute, and anything before it in the docblock will be ignored.
     *
     * @param attributeName The name of the attribute.
     */
    public setLeadingAttribute(
        attributeName: string | null,
        validator: ValidatorFn<any> = Validator.validateString,
    ): this {
        if (attributeName === null) {
            this.leadingAttribute = null;
        } else {
            this.leadingAttribute = {
                attributeName,
                isMultiline: false,
                regexp: this.makeRegexp(attributeName),
                validator,
            };
        }

        return this;
    }

    /**
     * Add an attribute to be parsed out of the docblock.
     *
     * @param attributeName The name of the attribute.
     * @param isMultiline Whether or not this is a multiline attribute.
     */
    public addAttribute(
        attributeName: string,
        options: {
            validator?: ValidatorFn<any>;
            isMultiline?: boolean;
        } = {},
    ): this {
        options = options ?? {};
        const { isMultiline = false, validator = Validator.validateString } = options;
        this.attributes.push({
            attributeName,
            isMultiline,
            regexp: this.makeRegexp(attributeName),
            validator,
        });

        return this;
    }

    public addIntAttribute(attributeName: string): this {
        return this.addAttribute(attributeName, {
            validator: Validator.validateInt,
        });
    }

    public addArrayAttribute(attributeName: string): this {
        return this.addAttribute(attributeName, {
            validator: Validator.validateJsonArray,
        });
    }

    /**
     * Parse a docblock string into an object.
     *
     * @param docblock The docblock to parse.
     * @generic T The return type.
     */
    public parse(docblock: string, startLine: number = 0): IParseResult<T> | null {
        // Quick bailout that may save us some work.
        if (this.leadingAttribute && !docblock.includes(`@${this.leadingAttribute.attributeName}`)) {
            return null;
        }

        // Split it into lines.
        const lines = docblock.split("\n");

        let result: Record<string, any> = {};
        let errors: IParseError[] = [];

        let matchedLeadingAttribute = this.leadingAttribute === null ? true : false;
        let currentLineAttribute: IAttribute | null = null;
        let currentValue = "";

        function handleValidation(attribute: IAttribute<any>, value: string, commentLine: number): boolean {
            const validated = attribute.validator(value);
            if (validated.success) {
                result[attribute.attributeName] = validated.value;
                return true;
            } else {
                errors.push({
                    message: validated.error.replace("{value}", "`" + value + "`"),
                    line: startLine + commentLine,
                });
                return false;
            }
        }

        function clearCurrentAttribute(commentLine: number) {
            if (currentLineAttribute) {
                handleValidation(currentLineAttribute, currentValue, commentLine);
                currentLineAttribute = null;
            }
        }

        lines.forEach((line, commentLine) => {
            if (!matchedLeadingAttribute) {
                const matchedLeading = this.leadingAttribute?.regexp?.exec(line);
                // Try to match the leading attribute
                if (matchedLeading) {
                    // We matched
                    // Add it to the result.
                    result[this.leadingAttribute!.attributeName] = matchedLeading.groups?.attributeValue ?? "";
                    matchedLeadingAttribute = true;
                } else {
                    // We haven't match our leading attribute yet. Don't parse anything.
                    return;
                }
            }

            const match = this.matchAttribute(line);
            if (match) {
                if (currentLineAttribute) {
                    // We have an existing line attribute to clear.
                    clearCurrentAttribute(commentLine);
                }

                const { attribute, value } = match;
                if (attribute.isMultiline) {
                    // We're starting a multiline value, so start building it.
                    currentLineAttribute = attribute;
                    currentValue = value;
                } else {
                    // This is a totally independant value.
                    // Validate an save it.
                    handleValidation(attribute, value, commentLine);
                }
            } else {
                if (line.match(GENERIC_AT_LINE)) {
                    // This is an @ item, but we don't have a match.
                    // We still need to clear the current line though.
                    clearCurrentAttribute(commentLine);
                } else if (currentLineAttribute) {
                    // This is a extra text for the current line.
                    // Try to match some text.
                    const lineMatch = line.match(GENERIC_TEXT_LINE);
                    if (lineMatch) {
                        currentValue += "\n" + lineMatch.groups?.text ?? "";
                    }
                } else {
                    // this is a line that doesn't actually match at all.
                    // Clear if we can.
                    clearCurrentAttribute(commentLine);
                }
            }
        });

        // Clear the last attribute if necessary.
        if (currentLineAttribute) {
            clearCurrentAttribute(currentLineAttribute);
        }

        if (this.leadingAttribute && !matchedLeadingAttribute) {
            return null;
        }

        return { value: result as T, errors };
    }

    private makeRegexp(attributeName: string): RegExp {
        const regexp = new RegExp(`\\*(?<leadingWhiteSpace>\\s+)@${attributeName}\\s+(?<attributeValue>.*)`);
        return regexp;
    }

    private matchAttribute(line: string): IAttributeMatch | null {
        for (const attr of this.attributes) {
            const match = attr.regexp.exec(line);
            if (match) {
                return {
                    attribute: attr,
                    leadingWhiteSpaceCount: match.groups?.leadingWhiteSpace?.length ?? 0,
                    value: match.groups?.attributeValue ?? "",
                };
            }
        }

        return null;
    }
}
