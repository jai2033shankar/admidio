<?php
/******************************************************************************
 * Klasse erweitert das PHP-DateTime-Objekt um einige nuetzliche Funktionen
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 https://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

/**
 * Class DateTimeExtended
 */
class DateTimeExtended extends DateTime
{
    private $valid;

    /**
     * es muss das Datum und das dazugehoerige Format uebergeben werden
     * @param string $date     String mit dem Datum
     * @param string $format   das zum Datum passende Format (Schreibweise aus date())
     * @param object $timezone DateTimeZone
     */
    public function __construct($date, $format, $timezone = null)
    {
        $datetime = DateTime::createFromFormat($format, $date);

        if ($datetime === false)
        {
            $this->valid = false;
            parent::__construct(null, $timezone);
        }
        else
        {
            $this->valid = true;
            parent::__construct($datetime->format('Y-m-d H:i:s'), $timezone);
        }
    }

    /**
     * gibt true oder false zurueck, je nachdem ob DateTime gueltig ist
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * berechnet aus dem Datum das Alter einer Person
     * @return int
     */
    public function getAge()
    {
        $interval = $this->diff(new DateTime('now'));
        $age = $interval->y;

        return $age;
    }

    /**
     * Returns an array with all 7 weekdays with full name in the specific language.
     * @param  int             $weekday The number of the weekday for which the name should be returned (1 = Monday ...)
     * @return string|string[] with all 7 weekday or if param weekday is set than the full name of that weekday
     */
    public static function getWeekdays($weekday = 0)
    {
        global $gL10n;

        $weekdays = array(1 => $gL10n->get('SYS_MONDAY'),
            2 => $gL10n->get('SYS_TUESDAY'),
            3 => $gL10n->get('SYS_WEDNESDAY'),
            4 => $gL10n->get('SYS_THURSDAY'),
            5 => $gL10n->get('SYS_FRIDAY'),
            6 => $gL10n->get('SYS_SATURDAY'),
            7 => $gL10n->get('SYS_SUNDAY')
        );

        if ($weekday > 0)
        {
            return $weekdays[$weekday];
        }
        else
        {
            return $weekdays;
        }
    }

    /**
     * The method will convert a date format with the syntax of date()
     * to a syntax that is known by the bootstrap datepicker plugin.
     * e.g.: input: 'd.m.Y' output: 'dd.mm.yyyy'
     * e.g.: input: 'j.n.y' output: 'd.m.yy'
     * @param  string $format Optional a format could be given in the date() syntax that should be transformed.
     *                        If no format is set then the format of the class constructor will be used.
     * @return string Return the transformed format that is valid for the datepicker.
     */
    public static function getDateFormatForDatepicker($format = 'Y-m-d')
    {
        $destFormat  = '';
        $formatArray = str_split($format);

        foreach ($formatArray as $formatChar)
        {
            switch($formatChar)
            {
                case 'd':
                    $destFormat .= 'dd';
                    break;
                case 'j':
                    $destFormat .= 'd';
                    break;
                case 'l':
                    $destFormat .= 'DD';
                    break;
                case 'D':
                    $destFormat .= 'D';
                    break;
                case 'm':
                    $destFormat .= 'mm';
                    break;
                case 'n':
                    $destFormat .= 'm';
                    break;
                case 'F':
                    $destFormat .= 'MM';
                    break;
                case 'M':
                    $destFormat .= 'M';
                    break;
                case 'Y':
                    $destFormat .= 'yyyy';
                    break;
                case 'y':
                    $destFormat .= 'yy';
                    break;
                default:
                    $destFormat .= $formatChar;
                    break;
            }
        }

        return $destFormat;
    }
}
