<?php
/*****************************************************************************/
/** @class ModuleAnnouncements
 *  @brief This class reads announcement recordsets from database
 *
 *  This class reads all available recordsets from table announcements
 *  and returns an Array with results, recordsets and validated parameters from $_GET Array.
 *  @par Returned Array
 *  @code
 *  Array
 *  (
 *      [numResults] => 3
 *      [limit] => 10
 *      [totalCount] => 3
 *      [recordset] => Array
 *          (
 *              [0] => Array
 *                  (
 *                      [0] => 3
 *                      [ann_id] => 3
 *                      [1] => DEMO
 *                      [ann_org_shortname] => DEMO
 *                      [2] => 1
 *                      [ann_global] => 1
 *                      [3] => Willkommen im Demobereich
 *                      [ann_headline] => Willkommen im Demobereich
 *                      [4] => <p>In diesem Bereich kannst du mit Admidio herumspielen und schauen, ....</p>
 *                      [ann_description] => <p>In diesem Bereich kannst du mit Admidio herumspielen und schauen, ....</p>
 *                      [5] => 1
 *                      [ann_usr_id_create] => 1
 *                      [6] => 2013-07-18 00:00:00
 *                      [ann_timestamp_create] => 2013-07-18 00:00:00
 *                      [7] =>
 *                      [ann_usr_id_change] =>
 *                      [8] =>
 *                      [ann_timestamp_change] =>
 *                      [9] => Paul Webmaster
 *                      [create_name] => Paul Webmaster
 *                      [10] =>
 *                      [change_name] =>
 *                  )
 *          )
 *      [parameter] => Array
 *          (
 *              [active_role] => 1
 *              [calendar-selection] => 1
 *              [cat_id] => 0
 *              [category-selection] => 0,
 *              [date] => ''
 *              [daterange] => Array
 *                  (
 *                      [english] => Array
 *                          (
 *                              [start_date] => 2013-09-16 // current date
 *                              [end_date] => 9999-12-31
 *                          )
 *
 *                      [system] => Array
 *                          (
 *                              [start_date] => 16.09.2013 // current date
 *                              [end_date] => 31.12.9999
 *                          )
 *                  )
 *              [headline] => Ankündigungen
 *              [id] => 0
 *              [mode] => Default
 *              [order] => 'ASC'
 *              [startelement] => 0
 *              [view_mode] => Default
 *          )
 *  )
 *  @endcode
 */
/******************************************************************************
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 https://www.gnu.org/licenses/gpl-2.0.html
 *
 ******************************************************************************/

class ModuleAnnouncements extends Modules
{
    protected $getConditions;   ///< String with SQL condition

    /**
     * Get number of available announcements
     * @Return Returns the total count and push it in the array
     */
    public function getDataSetCount()
    {
        global $gCurrentOrganization;
        global $gDb;

        $sql = 'SELECT COUNT(1) as count
                  FROM '. TBL_ANNOUNCEMENTS. '
                 WHERE (  ann_org_shortname = \''. $gCurrentOrganization->getValue('org_shortname'). '\'
                    OR (   ann_global   = 1
                   AND ann_org_shortname IN ('.$gCurrentOrganization->getFamilySQL(true).') ))
                       '.$this->getConditions.'';
        $result = $gDb->query($sql);
        $row    = $gDb->fetch_array($result);
        return $row['count'];
    }

    /**
     * Get all records and push it to the array
     * @return Returns the Array with results, recordsets and validated parameters from $_GET Array
     */
    public function getDataSet($startElement=0, $limit=NULL)
    {
        global $gCurrentOrganization;
        global $gPreferences;
        global $gProfileFields;
        global $gDb;

        //Parameter
        if($limit == NULL)
        {
            $announcementsPerPage = $gPreferences['announcements_per_page'];
        }

        //Bedingungen
        if($this->getParameter('id') > 0)
        {
            $this->getConditions = 'AND ann_id ='. $this->getParameter('id');
        }
        // Search announcements to date
        elseif(strlen($this->getParameter('dateStartFormatEnglish')) > 0)
        {
            $this->getConditions = 'AND ann_timestamp_create BETWEEN \''.$this->getParameter('dateStartFormatEnglish').' 00:00:00\' AND \''.$this->getParameter('dateEndFormatEnglish').' 23:59:59\'';
        }

        if($gPreferences['system_show_create_edit'] == 1)
        {
            // show firstname and lastname of create and last change user
            $additionalFields = '
                cre_firstname.usd_value || \' \' || cre_surname.usd_value as create_name,
                cha_firstname.usd_value || \' \' || cha_surname.usd_value as change_name ';
            $additionalTables = '
              LEFT JOIN '. TBL_USER_DATA .' cre_surname
                ON cre_surname.usd_usr_id = ann_usr_id_create
               AND cre_surname.usd_usf_id = '.$gProfileFields->getProperty('LAST_NAME', 'usf_id').'
              LEFT JOIN '. TBL_USER_DATA .' cre_firstname
                ON cre_firstname.usd_usr_id = ann_usr_id_create
               AND cre_firstname.usd_usf_id = '.$gProfileFields->getProperty('FIRST_NAME', 'usf_id').'
              LEFT JOIN '. TBL_USER_DATA .' cha_surname
                ON cha_surname.usd_usr_id = ann_usr_id_change
               AND cha_surname.usd_usf_id = '.$gProfileFields->getProperty('LAST_NAME', 'usf_id').'
              LEFT JOIN '. TBL_USER_DATA .' cha_firstname
                ON cha_firstname.usd_usr_id = ann_usr_id_change
               AND cha_firstname.usd_usf_id = '.$gProfileFields->getProperty('FIRST_NAME', 'usf_id');
        }
        else
        {
            // show username of create and last change user
            $additionalFields = ' cre_username.usr_login_name as create_name,
                                  cha_username.usr_login_name as change_name ';
            $additionalTables = '
              LEFT JOIN '. TBL_USERS .' cre_username
                ON cre_username.usr_id = ann_usr_id_create
              LEFT JOIN '. TBL_USERS .' cha_username
                ON cha_username.usr_id = ann_usr_id_change ';
        }

        //read announcements from database
        $sql = 'SELECT ann.*, '.$additionalFields.'
                  FROM '. TBL_ANNOUNCEMENTS. ' ann
                       '.$additionalTables.'
                 WHERE (  ann_org_shortname = \''. $gCurrentOrganization->getValue('org_shortname'). '\'
                    OR (   ann_global   = 1
                   AND ann_org_shortname IN ('.$gCurrentOrganization->getFamilySQL(true).') ))
                       '.$this->getConditions.'
                 ORDER BY ann_timestamp_create DESC';

        // Check if limit was set
        if($limit > 0)
        {
            $sql .= ' LIMIT '.$limit;
        }
        if($startElement != 0)
        {
            $sql .= ' OFFSET '.$startElement;
        }

        $announcementsStatement = $gDb->query($sql);

        //array for results
        $announcements['recordset']  = $announcementsStatement->fetchAll();
        $announcements['numResults'] = $announcementsStatement->rowCount();
        $announcements['limit']      = $limit;
        $announcements['totalCount'] = $this->getDataSetCount();
        $announcements['parameter']  = $this->getParameters();

        return $announcements;
    }

    /** Set a date range in which the dates should be searched. The method will fill
     *  4 parameters @b dateStartFormatEnglish, @b dateStartFormatEnglish,
     *  @b dateEndFormatEnglish and @b dateEndFormatAdmidio that could be read with
     *  getParameter and could be used in the script.
     *  @param $dateRangeStart A date in english or Admidio format that will be the start date of the range.
     *  @param $dateRangeEnd   A date in english or Admidio format that will be the end date of the range.
     *  @return Returns false if invalid date format is submitted
     */
    public function setDateRange($dateRangeStart, $dateRangeEnd)
    {
        global $gPreferences;

        if($dateRangeStart === '')
        {
            $dateRangeStart  = '1970-01-01';
            $dateRangeEnd    = DATE_NOW;
        }

        // Create date object and format date_from in English format and system format and push to daterange array
        $objDate = new DateTimeExtended($dateRangeStart, 'Y-m-d');
        if($objDate->isValid())
        {
            $this->setParameter('dateStartFormatEnglish', substr($objDate->getDateTimeString(), 0, 10));
            $this->setParameter('dateStartFormatAdmidio', $objDate->format($gPreferences['system_date']));
        }
        else
        {
            // check if date_from  has system format
            $objDate = new DateTimeExtended($dateRangeStart, $gPreferences['system_date']);

            if($objDate->isValid())
            {
                $this->setParameter('dateStartFormatEnglish', substr($objDate->getDateTimeString(), 0, 10));
                $this->setParameter('dateStartFormatAdmidio', $objDate->format($gPreferences['system_date']));
            }
            else
            {
                return false;
            }
        }

        // Create date object and format date_to in English format and sytem format and push to daterange array
        $objDate = new DateTimeExtended($dateRangeEnd, 'Y-m-d');
        if($objDate->isValid())
        {
            $this->setParameter('dateEndFormatEnglish', substr($objDate->getDateTimeString(), 0, 10));
            $this->setParameter('dateEndFormatAdmidio', $objDate->format($gPreferences['system_date']));
        }
        else
        {
            // check if date_from  has system format
            $objDate = new DateTimeExtended($dateRangeEnd, $gPreferences['system_date']);

            if($objDate->isValid())
            {
                $this->setParameter('dateEndFormatEnglish', substr($objDate->getDateTimeString(), 0, 10));
                $this->setParameter('dateEndFormatAdmidio', $objDate->format($gPreferences['system_date']));
            }
            else
            {
                return false;
            }
        }

    }
}
?>
