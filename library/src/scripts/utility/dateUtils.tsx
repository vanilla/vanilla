/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */
import moment from "moment";

export const formatDateMonth = (date: string | number | Date, format: string = "DD MMM YYYY") =>
    moment(date).format(format);
