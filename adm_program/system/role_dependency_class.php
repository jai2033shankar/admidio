<?php
/******************************************************************************
 * Klasse fuer Datenbanktabelle adm_role_dependencies
 *
 * Copyright    : (c) 2004 - 2007 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Roland Meuthen
 *
 ******************************************************************************
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *****************************************************************************/

class RoleDependency
{
    var $db_connection;

    var $role_id_parent;
    var $role_id_child;
    var $comment;
    var $usr_id;
    var $timestamp;

    var $role_id_parent_orig;
    var $role_id_child_orig;

    var $persisted;


    // Konstruktor
    function RoleDependency($connection)
    {
        $this->db_connection = $connection;
        $this->clear();
    }

    // Rollenabhaengigkeit aus der Datenbank auslesen
    function get($childRoleId,$parentRoleId)
    {

        $this->clear();

        if($childRoleId > 0 && $parentRoleId > 0
        && is_numeric($childRoleId) && is_numeric($parentRoleId))
        {
            $sql = "SELECT * FROM ". TBL_ROLE_DEPENDENCIES.
                   " WHERE rld_rol_id_child = $childRoleId
                       AND rld_rol_id_parent = $parentRoleId";
            $result = mysql_query($sql, $this->db_connection);
            db_error($result,__FILE__,__LINE__);

            if($row = mysql_fetch_object($result))
            {
                $this->role_id_parent      = $row->rld_rol_id_parent;
                $this->role_id_child       = $row->rld_rol_id_child;
                $this->comment             = $row->rld_comment;
                $this->timestamp           = $row->rld_timestamp;
                $this->usr_id              = $row->rld_usr_id;

                $this->role_id_parent_orig = $row->rld_rol_id_parent;
                $this->role_id_child_orig  = $row->rld_rol_id_child;
            }
            else
            {
                $this->clear();
            }
        }
        else
        {
            $this->clear();
        }
    }

    // alle Klassenvariablen wieder zuruecksetzen
    function clear()
    {
        $this->role_id_parent      = 0;
        $this->role_id_child       = 0;
        $this->comment             = "";
        $this->usr_id              = 0;
        $this->timestamp           = "";

        $this->role_id_parent_orig = 0;
        $this->role_id_child_orig  = 0;

        $persisted = false;
    }

    // Es muss die ID des eingeloggten Users uebergeben werden,
    // damit die Aenderung protokolliert werden kann
    function update($login_user_id)
    {
        if(!isEmpty() && $login_user_id > 0 && is_numeric($login_user_id))
        {
            $act_date = date("Y-m-d H:i:s", time());

            $sql = "UPDATE ". TBL_ROLE_DEPENDENCIES. " SET rld_rol_id_parent = '$this->role_id_parent'
                                                         , rld_rol_id_child  = '$this->role_id_child'
                                                         , rld_comment        = '$this->comment'
                                                         , rld_timestamp      = '$act_date'
                                                         , rld_usr_id         = $login_user_id " .
                    "WHERE rld_rol_id_parent = '$this->role_id_parent_orig'" .
                      "AND rld_rol_id_child  = '$this->role_id_child_orig'";

            $result = mysql_query($sql, $this->db_connection);
            db_error($result,__FILE__,__LINE__);
            $persisted = true;
            return 0;
        }
        return -1;
    }

    function insert($login_user_id)
    {
        if(!$this->isEmpty() && $login_user_id > 0 && is_numeric($login_user_id))
        {
            $act_date = date("Y-m-d H:i:s", time());

            $sql = "INSERT INTO ". TBL_ROLE_DEPENDENCIES. " (rld_rol_id_parent,rld_rol_id_child,rld_comment,rld_usr_id,rld_timestamp)
                                                            VALUES
                                                            ($this->role_id_parent
                                                         , $this->role_id_child
                                                         , '$this->comment'
                                                         , $login_user_id
                                                         , '$act_date') ";
            $result = mysql_query($sql, $this->db_connection);
            db_error($result,__FILE__,__LINE__);
            $persisted = true;
            return 0;
        }
        return -1;
    }

    function isEmpty()
    {
        if ($this->role_id_parent == 0 && $this->role_id_child == 0)
            return 1;
        else
            return 0;

    }

    // aktuelle Rollenabhaengigkeit loeschen
    function delete()
    {
        $sql    = "DELETE FROM ". TBL_ROLE_DEPENDENCIES.
                   " WHERE rld_rol_id_child = $this->role_id_child_orig " .
                     "AND rld_rol_id_parent = $this->role_id_parent_orig";
        $result = mysql_query($sql, $this->db_connection);
        db_error($result,__FILE__,__LINE__);

        $this->clear();
    }

    function getParentRoles($dbConnection,$childId)
    {
        if($childId > 0 && is_numeric($childId))
        {
            $allParentIds = array();

            $sql = "SELECT rld_rol_id_parent FROM ". TBL_ROLE_DEPENDENCIES.
                   " WHERE rld_rol_id_child = $childId ";
            $result = mysql_query($sql, $dbConnection);
            db_error($result,__FILE__,__LINE__);

            $num_rows = mysql_num_rows($result);
            if ($num_rows)
            {
                while ($row = mysql_fetch_object($result))
                {
                    $allParentIds[] = $row->rld_rol_id_parent;
                }
            }

            return  $allParentIds;
        }
        return -1;
    }

    function getChildRoles($dbConnection,$parentId)
    {
        if($parentId > 0 && is_numeric($parentId))
        {
            $allChildIds = array();

            $sql = "SELECT rld_rol_id_child FROM ". TBL_ROLE_DEPENDENCIES.
                   " WHERE rld_rol_id_parent = $parentId ";
            $result = mysql_query($sql, $dbConnection);
            db_error($result,__FILE__,__LINE__);

            $num_rows = mysql_num_rows($result);
            if ($num_rows)
            {
                while ($row = mysql_fetch_object($result))
                {
                    $allChildIds[] = $row->rld_rol_id_child;
                }
            }

            return  $allChildIds;
        }
        return -1;
    }

    function setParent($parentId)
    {
        if($parentId > 0 && is_numeric($parentId))
        {
            $this->role_id_parent = $parentId;
            $persisted = false;
            return 0;
        }
        return -1;
    }

    function setChild($childId)
    {
        if($childId > 0 && is_numeric($childId))
        {
            $this->role_id_child = $childId;
            $persisted = false;
            return 0;
        }
        return -1;
    }

    function updateMembership()
    {
        if(0 != $this->role_id_parent and 0 != $this->role_id_child )
        {
            $sql = "SELECT mem_usr_id FROM ". TBL_MEMBERS.
                       " WHERE mem_rol_id = $this->role_id_child ;";
            $result = mysql_query($sql, $this->db_connection);
            db_error($result,__FILE__,__LINE__);


            $num_rows = mysql_num_rows($result);
            if ($num_rows)
            {
                $sql="  INSERT IGNORE INTO ". TBL_MEMBERS. " (mem_rol_id, mem_usr_id, mem_begin, mem_valid, mem_leader) VALUES ";

                while ($row = mysql_fetch_object($result))
                {
                    $sql .= "($this->role_id_parent, $row->mem_usr_id, NOW(), 1, 0),";
                }
                //Das letzte Komma wieder wegschneiden
                $sql = substr($sql,0,-1);
                
                $result2 = mysql_query($sql, $this->db_connection);
                db_error($result2,__FILE__,__LINE__);
            }
            return 0;
        }
        return -1;
    }

    function removeChildRoles($dbConnection,$parentId)
    {
        if($parentId > 0 && is_numeric($parentId))
        {
            $allChildIds = array();

            $sql = "DELETE FROM ". TBL_ROLE_DEPENDENCIES.
                   " WHERE rld_rol_id_parent = $parentId ";
            $result = mysql_query($sql, $dbConnection);
            db_error($result,__FILE__,__LINE__);

            return  0;
        }
        return -1;
    }

}
?>