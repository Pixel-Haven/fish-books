import dayjs from "dayjs";

function timeAgo(
    date: Date | dayjs.Dayjs | string | undefined = undefined,
    locale: string = "en",
    withoutSuffix: boolean = false
) {
    const ago = dayjs(date).locale(locale).fromNow(withoutSuffix);
    if (locale === "dv") {
        return ago.split(" ").reverse().join(" ");
    }
    return ago;
}

// Default date format used throughout the application
export const DEFAULT_DATE_FORMAT = "DD/MM/YYYY"; // Change this to your preferred format

function formatDate(
    date: Date | dayjs.Dayjs | string | undefined = undefined,
    format: string = DEFAULT_DATE_FORMAT,
    locale: string = "en"
) {
    if (dayjs.isDayjs(date)) {
        return date.locale(locale).format(format);
    }
    return dayjs(date)
        .locale(locale)
        .format(format);
}

/**
 * Format amount as MVR (Maldivian Rufiyaa) currency
 * @param amount - The amount to format
 * @returns Formatted currency string (e.g., "Rf 1,234.50")
 */
function formatCurrency(amount: number | string | null | undefined): string {
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
    
    if (isNaN(numAmount)) {
        return 'Rf 0.00';
    }
    
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'MVR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount).replace('MVR', 'Rf');
}

export { timeAgo, formatDate, formatCurrency };
