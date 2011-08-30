<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Group class
**/
class Group extends CommonDBTM {


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Menu'][36];
      }
      return $LANG['common'][35];
   }


   function canCreate() {
      return Session::haveRight('group', 'w');
   }


   function canView() {
      return Session::haveRight('group', 'r');
   }


   function post_getEmpty () {

      $this->fields['is_requester'] = 1;
      $this->fields['is_assign']    = 1;
      $this->fields['is_notify']    = 1;
      $this->fields['is_itemgroup'] = 1;
      $this->fields['is_usergroup'] = 1;
   }


   function cleanDBonPurge() {
      global $DB;

      $gu = new Group_User();
      $gu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gt = new Group_Ticket();
      $gt->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      // Ticket rules use various _groups_id_*
      Rule::cleanForItemAction($this, '_groups_id%');
      Rule::cleanForItemCriteria($this, '_groups_id%');
      // GROUPS for RuleMailcollector
      Rule::cleanForItemCriteria($this, 'GROUPS');

      // Set no group to consumables
      $query = "UPDATE `glpi_consumables`
                SET `items_id` = '0'
                WHERE `items_id` = '".$this->fields['id']."'
                      AND `itemtype` = 'Group'";
      $DB->query($query);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate && Session::haveRight("group","r")) {
         switch ($item->getType()) {
            case 'Group' :
               $ong = array();
               if ($item->getField('is_itemgroup')) {
                  $ong[1] = $LANG['common'][111];
               }
               if ($item->getField('is_assign')) {
                  $ong[2] = $LANG['common'][112];
               }
               if ($item->getField('is_usergroup')
                   && Session::haveRight('config', 'r')
                   && AuthLdap::useAuthLdap()) {
                  $ong[3] = $LANG['setup'][3];
               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $LANG;

      switch ($item->getType()) {
         case 'Group' :
            switch ($tabnum) {
               case 1 :
                  $item->showItems(false);
                  return true;

               case 2 :
                  $item->showItems(true);
                  return true;

               case 3 :
                  $item->showLDAPForm($item->getID());
                  return true;

            }
            break;
      }
      return false;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();

      if ($this->fields['is_usergroup']) {
         $this->addStandardTab('User', $ong, $options);
      }
      if ($this->fields['is_notify']) {
         $this->addStandardTab('NotificationTarget', $ong, $options);
      }
      $this->addStandardTab('Group', $ong, $options);
      if ($this->fields['is_requester']) {
         $this->addStandardTab('Ticket', $ong, $options);
      }

      return $ong;
   }


   /**
   * Print the group form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $LANG;

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      $this->showTabs($options);
      $options['colspan']=4;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>".$LANG['common'][16]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='5' class='middle'>".$LANG['common'][25]."&nbsp;:&nbsp;</td>";
      echo "<td class='middle' rowspan='5'>";
      echo "<textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' colspan='4'>".$LANG['group'][0]."</td>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>&nbsp;</td>";
      echo "<td>".$LANG['job'][4]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      dropdown::showYesNo('is_requester', $this->fields['is_requester']);
      echo "</td>";
      echo "<td>".$LANG['job'][5]."&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      dropdown::showYesNo('is_assign', $this->fields['is_assign']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='b'>".$LANG['group'][1]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      dropdown::showYesNo('is_notify', $this->fields['is_notify']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' colspan='4'>".$LANG['group'][2]."</td>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>&nbsp;</td>";
      echo "<td>".$LANG['common'][96]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      dropdown::showYesNo('is_itemgroup', $this->fields['is_itemgroup']);
      echo "</td>";
      echo "<td>".$LANG['Menu'][14]."&nbsp;:&nbsp;&nbsp;";
      dropdown::showYesNo('is_usergroup', $this->fields['is_usergroup']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center'>";
      if (!$ID) {
         $template = "newtemplate";
         echo $LANG['computers'][14]."&nbsp;:&nbsp;";
         echo HTML::convDateTime($_SESSION["glpi_currenttime"]);

      } else {
         echo $LANG['common'][26]."&nbsp;:&nbsp;";
         echo HTML::convDateTime($this->fields["date_mod"]);
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print a good title for group pages
    *
    *@return nothing (display)
    **/
   function title() {
      global $LANG, $CFG_GLPI;

      $buttons = array();
      if (Session::haveRight("group", "w")
          && Session::haveRight("user_authtype", "w")
          && AuthLdap::useAuthLdap()) {

         $buttons["ldap.group.php"] = $LANG['setup'][3];
         $title = "";

      } else {
         $title = $LANG['Menu'][36];
      }

      Html::displayTitle($CFG_GLPI["root_doc"] . "/pics/groupes.png", $LANG['Menu'][36], $title,
                         $buttons);
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      if (AuthLdap::useAuthLdap()) {

         $tab[3]['table']     = $this->getTable();
         $tab[3]['field']     = 'ldap_field';
         $tab[3]['name']      = $LANG['setup'][260];
         $tab[3]['datatype']  = 'string';

         $tab[4]['table']     = $this->getTable();
         $tab[4]['field']     = 'ldap_value';
         $tab[4]['name']      = $LANG['setup'][601];
         $tab[4]['datatype']  = 'string';

         $tab[5]['table']     = $this->getTable();
         $tab[5]['field']     = 'ldap_group_dn';
         $tab[5]['name']      = $LANG['setup'][261];
         $tab[5]['datatype']  = 'string';
      }

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'is_recursive';
      $tab[6]['name']     = $LANG['entity'][9];
      $tab[6]['datatype'] = 'bool';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'is_requester';
      $tab[11]['name']          = $LANG['job'][4];
      $tab[11]['datatype']      = 'bool';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'is_assign';
      $tab[12]['name']          = $LANG['job'][5];
      $tab[12]['datatype']      = 'bool';

      $tab[13]['table']         = $this->getTable();
      $tab[13]['field']         = 'is_notify';
      $tab[13]['name']          = $LANG['group'][1];
      $tab[13]['datatype']      = 'bool';

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'is_itemgroup';
      $tab[14]['name']          = $LANG['search'][2]." ".$LANG['common'][96];
      $tab[14]['datatype']      = 'bool';

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'is_usergroup';
      $tab[15]['name']          = $LANG['search'][2]." ".$LANG['Menu'][14];
      $tab[15]['datatype']      = 'bool';

      return $tab;
   }


   function showLDAPForm ($ID) {
      global $LANG;

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      echo "<form name='groupldap_form' id='groupldap_form' method='post' action='".
             $this->getFormURL()."'>";
      echo "<div class='spaced'><table class='tab_cadre_fixe'>";

      if (Session::haveRight("config","r") && AuthLdap::useAuthLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][256]."&nbsp;:&nbsp;</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][260]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_field");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][601]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_value");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][257]."&nbsp;:&nbsp;</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][261]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_group_dn");
         echo "</td></tr>";
      }

      $options = array('colspan' => 1,
                       'candel'  => false);
      $this->showFormButtons($options);

      echo "</table></div></form>";
   }


   /**
    * Show items for the group
    *
    * @param $tech boolean, false search groups_id, true, search groups_id_tech
    */
   function showItems($tech) {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $this->fields['id'];

      if ($tech) {
         $types = $CFG_GLPI['linkgroup_tech_types'];
         $field = 'groups_id_tech';
      } else {
         $types = $CFG_GLPI['linkgroup_types'];
         $field = 'groups_id';
      }

      echo "<div class='spaced'>";
      echo "<form name='group_form' id='group_form_$field' method='post' action='".$this->getFormURL()."'>";
      echo "<table class='tab_cadre_fixe'><tr><th width='10'>&nbsp</th>";
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['common'][16]."</th><th>".$LANG['entity'][0]."</th></tr>";

      $nb = 0;
      foreach ($types as $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         $item->getEmpty();
         if (!$item->isField($field)) {
            continue;
         }
         $query = "SELECT *
                   FROM `".$item->getTable()."`
                   WHERE `$field` = '$ID'".
                         getEntitiesRestrictRequest(" AND ", getTableForItemType($itemtype), '', '',
                                                     $item->maybeRecursive());
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            $type_name = $item->getTypeName();
            $cansee    = $item->canView();
            $canedit   = $item->canUpdate();

            while ($data=$DB->fetch_array($result)) {
               echo "<tr class='tab_bg_1'><td>";
               if ($canedit) {
                  echo "<input type='checkbox' name='item[$itemtype][".$data["id"]."]' value='1'>";
                  $nb++;
               }
               $link = ($data["name"] ? $data["name"] : "(".$data["id"].")");

               if ($cansee) {
                  $link = "<a href='".$item->getFormURL()."?id=". $data["id"]."'>".$link."</a>";
               }

               echo "</td><td>$type_name</td><td>$link</td>";
               echo "<td>".Dropdown::getDropdownName("glpi_entities", $data['entities_id']);
               echo "</td></tr>";
            }
         }
      }
      echo "</table>";

      if ($nb) {
         Html::openArrowMassives("group_form_$field", true);
         echo $LANG['common'][35]."&nbsp;:&nbsp;";
         echo "<input type='hidden' name='field' value='$field'>";
         Dropdown::show('Group', array('entity'    => $this->fields["entities_id"],
                                       'used'      => array($this->fields["id"]),
                                       'condition' => ($tech ? '`is_assign`' : '`is_itemgroup`')));
         echo "&nbsp;";
         Html::closeArrowMassives(array('changegroup' => $LANG['buttons'][20]));
      }

      echo "</form></div>";
   }

}
?>