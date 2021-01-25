/**
 * @copyright 2020 Adam (charrondev) Charron
 * @license Proprietary
 */

import * as BabelParser from "@babel/parser";
import { DocBlockParser, IParseError } from "./DocBlockParser";
import fs from "fs";
import path from "path";
import glob from "globby";
import { IValidateResult, Validator } from "./Validator";

export interface IVariableDoc {
    // The key of the variable in dot notation.
    key: string;

    // The title of the variable.
    title: string;

    // A description for the variable.
    description?: string;

    // The version the variable was introduced.
    sinceVersion?: string;

    // The version the variable was deprecated in.
    deprecatedVersion?: string;

    // Alternate names for the variable (in dot notation).
    alts?: string;
}

export interface IVariable extends IVariableDoc {
    // Type info.
    type: string | string[];
    enum?: string[];
    default?: string;
}

export interface IVariableGroup extends IVariableDoc {
    commonTitle?: string;
    commonDescription?: string;
    expand?: string;
}

interface IVariableError {
    filePath: string;
    line: number;
    message: string;
}

interface IParsedVariableResult {
    variables: IVariable[];
    variableGroups: IVariableDoc[];
    errors: IVariableError[];
}

export type VariableExpander = (variable: IVariableGroup) => IVariable[];

export interface ITypeExpander {
    type: string;
    expandType: VariableExpander;
}

/**
 * Class for parsing a javascript/typescript string into a group of variables.
 */
export class VariableParser {
    /** Hold the registered type expanders. */
    private typeExpanders: ITypeExpander[] = [];

    /**
     * Utility for validating an expander value.
     * @param val
     */
    private validateExpand = <X extends string = string>(val: string): IValidateResult<X> => {
        const expandTypes = this.typeExpanders.map((expander) => expander.type);
        if (expandTypes.includes(val.trim() as X)) {
            return Validator.valid(val.trim() as X);
        } else {
            return Validator.invalid(`{value} is not a valid expand type. Valid values are ${expandTypes.join(", ")}`);
        }
    };

    /**
     * Parser instance for extracting @varGroup annotations.
     */
    private varGroupDocParser = DocBlockParser.create<Partial<IVariableGroup> & { varGroup: string }>()
        .setLeadingAttribute("varGroup")
        .addAttribute("title")
        .addAttribute("description", { isMultiline: true })
        .addAttribute("commonTitle")
        .addAttribute("commonDescription")
        .addAttribute("expand", { validator: this.validateExpand })
        .addArrayAttribute("alts");

    /**
     * Parser instance for extracting @var annotations.
     */
    private varDocParser = DocBlockParser.create<Partial<IVariable> & { var: string }>()
        .setLeadingAttribute("var")
        .addAttribute("sinceVersion")
        .addAttribute("deprecatedVersion")
        .addAttribute("title")
        .addAttribute("description", { isMultiline: true })
        .addAttribute("default")
        .addAttribute("type", { validator: Validator.validateJsonSchemaType })
        .addAttribute("enum", { validator: Validator.validateStringUnion });

    /**
     * Static factory.
     */
    public static create(): VariableParser {
        return new VariableParser();
    }

    /**
     * Register a type expander.
     * @param expander The expander.
     *
     * @return Fluent this return.
     */
    public addTypeExpander(expander: ITypeExpander): this {
        this.typeExpanders.push(expander);
        return this;
    }

    /**
     * Parse a javascript string into variables.
     *
     * @param fileContents The contents of a javascript file.
     * @param filePath The path of the file (used for errors).
     */
    public parseString(fileContents: string, filePath: string): IParsedVariableResult {
        // Tokenize the JS with babel.
        const parsed = BabelParser.parse(fileContents, {
            sourceType: "module",
            sourceFilename: filePath,
            plugins: ["typescript", "classProperties"],
        });
        const comments = parsed.comments ?? [];

        let resultGroups: IVariableGroup[] = [];
        let resultVars: IVariable[] = [];
        let resultErrors: IVariableError[] = [];

        comments.forEach((comment: any) => {
            // Loop iterating through all comments in the javascript.

            /**
             * Track parsing errors in the file, and ensure correct file line numbers are applied.
             * @param parseErrors
             */
            function pushParseErrors(parseErrors: IParseError[]) {
                for (const error of parseErrors) {
                    resultErrors.push({
                        ...error,
                        // Since we are parsing each comment individually
                        // errors come back with line numbers relative to the comment start.
                        //
                        // Add the comment line number.
                        line: comment.loc.start.line + error.line,
                        filePath,
                    });
                }
            }

            const parsedGroup = this.varGroupDocParser.parse(comment.value);
            // Check first for an @varGroup annotation.
            if (parsedGroup) {
                const { value, errors } = parsedGroup;
                if (errors.length > 0) {
                    // Early bailout with errors.
                    pushParseErrors(errors);
                    return;
                }

                let { varGroup, title, commonTitle, ...rest } = value;
                if (title && !commonTitle) {
                    commonTitle = title;
                } else if (commonTitle && !title) {
                    title = commonTitle;
                } else if (!title && !commonTitle) {
                    commonTitle = title = varNameToTitle(varGroup);
                }
                const newGroup: IVariableGroup = {
                    ...rest,
                    title: commonTitle!,
                    commonTitle: commonTitle!,
                    key: varGroup,
                };
                resultGroups.push(newGroup);
                if (newGroup.expand) {
                    const expander = this.getExpander(newGroup.expand);
                    if (expander) {
                        // This varGroup had a matching expander. Be sure to apply them all.
                        resultVars.push(...expander.expandType(newGroup));
                    }
                }
            } else {
                const parsedVariable = this.varDocParser.parse(comment.value);
                if (parsedVariable) {
                    const { value, errors } = parsedVariable;
                    if (errors.length > 0) {
                        pushParseErrors(errors);
                        return;
                    }

                    let { var: varKey, title, description, ...rest } = value;
                    title = title ?? varNameToTitle(varKey);

                    // We may have a title prefix we need to apply.
                    let parentGroupKey = trimVariableKey(varKey);
                    let parentGroup: IVariableGroup | undefined = undefined;
                    while (parentGroupKey && parentGroup === undefined) {
                        parentGroup = resultGroups.find((group) => group.key === parentGroupKey);
                        if (!parentGroup) {
                            // Pop off another dot.
                            parentGroupKey = trimVariableKey(parentGroupKey);
                        }
                    }

                    if (parentGroup?.commonTitle) {
                        title = `${parentGroup.commonTitle.trim()} - ${title.trim()}`;
                    }

                    // If we have a common description, be sure to apply it.
                    if (parentGroup?.commonDescription) {
                        if (!description) {
                            description = parentGroup.commonDescription;
                        } else {
                            description += "\n" + parentGroup.commonDescription;
                        }
                    }

                    const newVar: IVariable = {
                        ...rest,
                        title,
                        description,
                        key: varKey,
                        type: rest.type ?? "string",
                    };
                    resultVars.push(newVar);
                }
            }
        });

        return {
            variableGroups: resultGroups,
            variables: resultVars,
            errors: resultErrors,
        };
    }

    /**
     * Parse a file on the file system into variable results.
     *
     * @param fileRoot The root of the file (will not be included in error file paths).
     * @param filePath The rest of the file path (will be included in error file paths).
     */
    public parseFile(fileRoot: string, filePath: string): IParsedVariableResult {
        const fileContents = fs.readFileSync(path.resolve(fileRoot, filePath), "utf8");
        return this.parseString(fileContents, filePath);
    }

    /**
     * Create an async iterator to parse multiple files.
     *
     * @param fileRoot The root of the file (will not be included in error file paths).
     * @param fileGlob A glob of files to match (relative to the fileRoot).
     */
    public async *parseFiles(fileRoot: string, fileGlob: string) {
        const fileStream = glob.stream(path.resolve(fileRoot, fileGlob));
        for await (const file of fileStream) {
            if (file.includes("node_modules")) {
                continue;
            }
            const relative = path.relative(fileRoot, file as string);
            const result: [string, IParsedVariableResult] = [relative, this.parseFile(fileRoot, relative)];
            yield result;
        }
    }

    /**
     * Utility to get an expander for a string type.
     *
     * @param forType.
     */
    private getExpander(forType: string | string[]): ITypeExpander | null {
        if (Array.isArray(forType)) {
            return null;
        }
        return this.typeExpanders.find((expander) => expander.type === forType) ?? null;
    }
}

/**
 * Trim off the end of a variable key.
 * @example thing.nested.paddings -> thing.nested
 * @param key The key to start.
 * @return Either the trimmed key or null if there wasn't anything to trim.
 */
function trimVariableKey(varKey: string): string | null {
    const lastIndex = varKey.lastIndexOf(".");
    if (lastIndex > 0) {
        return varKey.slice(0, lastIndex);
    } else {
        return null;
    }
}

/**
 * If there was no title try to give a decent fallback.
 * Eg. "this.nest.paddings" -> "Paddings"
 *
 * @param varName The variable name to convert.
 */
function varNameToTitle(varName: string): string {
    const split = varName.split(".");
    return titleCase(split[split.length - 1]);
}

function titleCase(input: string): string {
    const spaced = input.replace(/([A-Z])/g, " $1").trim();
    return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}

function capitalizeFirstChar(input: string): string {
    const first = input.slice(0, 1);
    const rest = input.slice(1);
    return first.toLocaleUpperCase() + rest;
}
