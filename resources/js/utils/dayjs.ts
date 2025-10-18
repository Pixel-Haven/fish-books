import "dayjs/locale/ar";
import "dayjs/locale/dv";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc'
import timezone from "dayjs/plugin/timezone";
import updateLocale from "dayjs/plugin/updateLocale";
import relativeTime from "dayjs/plugin/relativeTime";
import localizedFormat from 'dayjs/plugin/localizedFormat'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import preParsePostFormat from "dayjs/plugin/preParsePostFormat";

function setupDayJS() {
    dayjs.extend(utc)
    dayjs.extend(timezone);
    dayjs.extend(updateLocale);
    dayjs.extend(relativeTime);
    dayjs.extend(localizedFormat);
    dayjs.extend(customParseFormat);
    dayjs.extend(preParsePostFormat);

    dayjs.updateLocale("dv", {
        months: 'ޖެނުއަރީ_ފެބްރުއަރީ_މާރިޗް_އެޕްރީލް_މެއި_ޖޫން_ޖުލައި_އޯގަސްޓް_ސެޕްޓެމްބަރ_އޮކްޓޯބަރ_ނޮވެމްބަރ_ޑިސެމްބަރ'.split('_'),
    });
}

export default setupDayJS;