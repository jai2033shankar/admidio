<?php
/*****************************************************************************/
/** @class ComponentUpdate
 *  @brief Manage the update of a component from the actual version to the target version
 *
 *  The class is an extension to the component class and will handle the update of a
 *  component. It will read the database version from the component and set this as
 *  source version. Then you should set the target version. The class will then search
 *  search for specific update xml files in special directories. For the system this should be
 *  @b adm_install/db_scripts and for plugins there should be an install folder within the
 *  plugin directory. The xml files should have the prefix update and than the main und subversion 
 *  within their filename e.g. @b update_3_0.xml .
 *  @par Examples
 *  @code // update the system module to the actual filesystem version
 *  $componentUpdateHandle = new ComponentUpdate($gDb);
 *  $componentUpdateHandle->readDataByColumns(array('com_type' => 'SYSTEM', 'com_name_intern' => 'CORE'));
 *  $componentUpdateHandle->setTargetVersion(ADMIDIO_VERSION);
 *  $componentUpdateHandle->update();@endcode
 */
/*****************************************************************************
 *
 *  Copyright    : (c) 2004 - 2013 The Admidio Team
 *  Homepage     : http://www.admidio.org
 *  License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

require_once(SERVER_PATH. '/adm_program/system/classes/component.php');
 
class ComponentUpdate extends Component
{
	private $updateFinished;    			///< Flag that will store if the update prozess of this version was successfully finished
	private $xmlObject;			            ///< The SimpleXML object with all the update steps
    private $currentVersionArray;           ///< This is the version the component has actually before update. Each array element contains one part of the version.
    private $targetVersionArray;            ///< This is the version that is stored in the files of the component. Each array element contains one part of the version.
    
    /** Will open a XML file of a specific version that contains all the update steps that
     *  must be passed to successfully update Admidio to this version
     *  @param $version Contains a string with the main version number e.g. 2.4 or 3.0 .
     *                  The version should NOT contain the minor version numbers e.g. 2.4.2
     */
    private function createXmlObject($mainVersion, $subVersion)
    {
        // update of Admidio core has another path for the xml files as plugins
        if($this->getValue('com_type') == 'SYSTEM')
        {
            $updateFile = SERVER_PATH. '/adm_install/db_scripts/update_'.$mainVersion.'_'.$subVersion.'.xml';
            
            if(file_exists($updateFile))
            {
                $this->xmlObject = new SimpleXMLElement($updateFile, 0, true);
                return true;
            }
        }
        return false;
    }
    
     
    /** Will execute the specific update step that is set through the parameter $xmlNode.
     *  If the step was successfully done the id will be stored in the component recordset
     *  so if the whole update crashs later we know that this step was successfully executed.
     *  When the node has an attribute @b database than this sql statement will only executed
     *  if the value of the attribute is equal to your current @b $gDbType .
     *  @param $xmlNode A SimpleXML node of the current update step.
     */
    private function executeStep($xmlNode)
    {
        global $g_tbl_praefix, $gDbType;
 
        $executeSql = true;
        
        if(strlen(trim($xmlNode[0])) > 0)
        {
            // if the sql statement is only for a special database and you do
            // not have this database then don't execute this statement
            if(isset($xmlNode['database']) && $xmlNode['database'] != $gDbType)
            {
                $executeSql = false;
            }
            
            if($executeSql == true)
            {
                // replace prefix with installation specific table prefix
                $sql = str_replace('%PREFIX%', $g_tbl_praefix, $xmlNode[0]);
                
                $this->db->query($sql);
            }

            // set the type if the id to integer because otherwise the system thinks it's not numeric !!!
            $stepId = $xmlNode['id'];
            settype($stepId, 'integer');
            
            // save the successful executed update step in database
            $this->setValue('com_update_step', $stepId);
            $this->save();
        }
    }
    
    /** Goes step by step through the update xml file of the current database version 
     *  and search for the maximum step. If the last step is found than the id of this step
     *  will be returned.
     *  @return Return the number of the last update step that was found in xml file of the current version.
     */
    public function getMaxUpdateStep()
    {
        $maxUpdateStep = 0;
        $this->currentVersion = explode('.', $this->getValue('com_version'));

        // open xml file for this version
        if($this->createXmlObject($this->currentVersion[0], $this->currentVersion[1]))
        {
            // go step by step through the SQL statements until the last one is found
            foreach($this->xmlObject->children() as $updateStep)
            {
                if($updateStep[0] != 'stop')
                {
                    $maxUpdateStep = $updateStep['id'];
                }
            }
        }
        return $maxUpdateStep;
    }

    /** Set the target version for the component after update. This information should be
     *  read from the files of the component.
     *  @param $version Target version of the component after update
     */
    public function setTargetVersion($version)
    {
        $this->targetVersion = explode('.', $version);
    }
    
    /** Do a loop through all versions start with the current version and end with the target version.
     *  Within every subversion the method will search for an update xml file and execute all steps 
     *  in this file until the end of file is reached. If an error occured then the update will be stopped.
     *  @return Return @b true if the update was successfull.
     */
    public function update()
    {
        global $gDebug;
    
        $this->updateFinished = false;
        $this->currentVersion = explode('.', $this->getValue('com_version'));
        $initialSubVersion    = $this->currentVersion[1];
    
        for($mainVersion = $this->currentVersion[0]; $mainVersion <= $this->targetVersion[0]; $mainVersion++)
        {
            // Set max subversion for iteration. If we are in the loop of the target main version 
            // then set target subversion to the max version
            if($mainVersion == $this->targetVersion[0])
            {
                $maxSubVersion = $this->targetVersion[1];
            }
            else
            {
                $maxSubVersion = 20;
            }
        
            for($subVersion = $initialSubVersion; $subVersion <= $maxSubVersion; $subVersion++)
            {
                // if version is not equal to current version then start update step with 0
                if($mainVersion != $this->currentVersion[0]
                || $subVersion  != $this->currentVersion[1])
                {
                    $this->setValue('com_update_step', 0);
                    $this->save();
                }
                
                // output of the version number for better debugging
                if($gDebug)
                {
                    error_log('Update to version '.$mainVersion.'.'.$subVersion);
                }
                
                // open xml file for this version
                if($this->createXmlObject($mainVersion, $subVersion))
                {
                    // go step by step through the SQL statements and execute them
                    foreach($this->xmlObject->children() as $updateStep)
                    {
                        if($updateStep['id'] > $this->getValue('com_update_step'))
                        {
                            $this->executeStep($updateStep);
                        }
                        elseif($updateStep[0] == 'stop')
                        {
                            $this->updateFinished = true;
                        }
                    }
                }
                
                // check if an php update file exists and then execute the script
                $phpUpdateFile = SERVER_PATH.'/adm_install/db_scripts/upd_'. $mainVersion. '_'. $subVersion. '_0_conv.php';
                
                if(file_exists($phpUpdateFile))
                {
                    include($phpUpdateFile);
                    $flagNextVersion = true;
                }
                
                // save current version to component
                $this->setValue('com_version', $mainVersion.'.'.$subVersion.'.0');
                $this->save();
            }
            
            // reset subversion because we want to start update for next main version with subversion 0
            $initialSubVersion = 0;
        }
    }
}
?>