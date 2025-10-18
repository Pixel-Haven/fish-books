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

function formatDate(
    date: Date | dayjs.Dayjs | string | undefined = undefined,
    format: string = "DD / MMMM / YYYY",
    locale: string = "en"
) {
    if (dayjs.isDayjs(date)) {
        return date.locale(locale).format(format);
    }
    return dayjs(date)
        .locale(locale)
        .format(format);
}

export { timeAgo, formatDate };
