import { autoformatArrow, autoformatLegal, autoformatLegalHtml, autoformatPunctuation } from "@udecode/plate-headless";
import { MyAutoformatRule } from "../typescript";
import { autoformatBlocks } from "./autoformatBlocks";
import { autoformatLists } from "./autoformatLists";
import { autoformatMarks } from "./autoformatMarks";

export const autoformatRules = [
    ...autoformatBlocks,
    ...autoformatLists,
    ...autoformatMarks,
    ...(autoformatPunctuation as MyAutoformatRule[]),
    ...(autoformatLegal as MyAutoformatRule[]),
    ...(autoformatLegalHtml as MyAutoformatRule[]),
    ...(autoformatArrow as MyAutoformatRule[]),
];
