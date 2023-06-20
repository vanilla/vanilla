export function dateRangeToString(dateRange: { start?: string; end?: string }): string | undefined {
    const { start, end } = dateRange;
    let dateRangeString: string | undefined;
    if (start && end) {
        if (start === end) {
            // Simple equality.
            dateRangeString = start;
        } else {
            // Date range
            dateRangeString = `[${start},${end}]`;
        }
    } else if (start) {
        // Only start date
        dateRangeString = `>=${start}`;
    } else if (end) {
        // Only end date.
        dateRangeString = `<=${end}`;
    }
    return dateRangeString;
}
