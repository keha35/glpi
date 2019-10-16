<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

class PlanningExternalEvent extends CommonDBTM {
   use PlanningEvent;

   public $dohistory = true;
   static $rightname = 'externalevent';

   const MANAGE_BG_EVENTS =   1024;

   static function getTypeName($nb = 0) {
      return _n('External event', 'External events', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   static function canUpdate() {
      // we permits globally to update this object,
      // as users can update their onw items
      return Session::haveRightsOr(self::$rightname, [
         CREATE,
         UPDATE,
         self::MANAGE_BG_EVENTS
      ]);
   }

   function canUpdateItem() {
      // if we don't have the right to manage background events,
      // we don't have the right to edit the item
      if ($this->fields["background"]
          && !Session::haveRight(self::$rightname, self::MANAGE_BG_EVENTS)) {
         return false;
      }

      // the current user can update only this own events without UPDATE right
      // but not bg one, see above
      if ($this->fields['users_id'] != Session::getLoginUserID()
          && !Session::haveRight(self::$rightname, UPDATE)) {
         return false;
      }

      return parent::canUpdateItem();
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $canedit    = $this->can($ID, UPDATE);
      $rand       = mt_rand();
      $rand_plan  = mt_rand();
      $rand_rrule = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      if ($canedit) {
         $tpl_class = 'PlanningExternalEventTemplate';
         echo "<tr class='tab_bg_1' style='vertical-align: top'>";
         echo "<td colspan='2'>".$tpl_class::getTypeName()."</td>";
         echo "<td colspan='2'>";
         $tpl_class::dropdown([
            'value'     => $this->fields['planningexternaleventtemplates_id'],
            'entity'    => $this->getEntityID(),
            'rand'      => $rand,
            'on_change' => "template_update$rand(this.value)"
         ]);

         $ajax_url = $CFG_GLPI["root_doc"]."/ajax/planning.php";
         $JS = <<<JAVASCRIPT
            function template_update{$rand}(value) {
               $.ajax({
                  url: '{$ajax_url}',
                  type: "POST",
                  data: {
                     action: 'get_externalevent_template',
                     planningexternaleventtemplates_id: value
                  }
               }).done(function(data) {
                  // set common fields
                  if (data.name.length > 0) {
                     $("#textfield_name{$rand}").val(data.name);
                  }
                  $("#dropdown_state{$rand}").trigger("setValue", data.state);
                  if (data.planningeventcategories_id > 0) {
                     $("#dropdown_planningeventcategories_id{$rand}")
                        .trigger("setValue", data.planningeventcategories_id);
                  }
                  $("#dropdown_background{$rand}").trigger("setValue", data.background);
                  if (data.text.length > 0) {
                     $("#text{$rand}").html(data.text);
                     if (contenttinymce = tinymce.get("text{$rand}")) {
                        contenttinymce.setContent(data.text);
                     }
                  }

                  // set planification fields
                  if (data.duration > 0) {
                     $("#dropdown_plan__duration_{$rand_plan}").trigger("setValue", data.duration);
                  }
                  $("#dropdown__planningrecall_before_time_{$rand_plan}")
                     .trigger("setValue", data.before_time);

                  // set rrule fields
                  if (data.rrule != null
                      && data.rrule.freq != null ) {
                     $("#dropdown_rrule_freq_{$rand_rrule}").trigger("setValue", data.rrule.freq);
                     $("#dropdown_rrule_interval_{$rand_rrule}").trigger("setValue", data.rrule.interval);
                     $("#showdate{$rand_rrule}").val(data.rrule.until);
                     $("#dropdown_rrule_byweekday_{$rand_rrule}").trigger("setValue", data.rrule.byweekday);
                     $("#dropdown_rrule_bymonth_{$rand_rrule}").trigger("setValue", data.rrule.bymonth);
                  }
               });
            }
JAVASCRIPT;
         echo Html::scriptBlock($JS);
         echo "</tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='2'>".__('Title')."</td>";
      echo "<td colspan='2'>";
      if (!$ID) {
         echo Html::hidden('users_id', ['value' => $this->fields['users_id']]);
      }
      if ($canedit) {
         Html::autocompletionTextField($this, "name", [
            'size'   => '80',
            'entity' => -1,
            'user'   => $this->fields["users_id"],
            'rand'   => $rand
         ]);
      } else {
         echo $this->fields['name'];
      }
      if (isset($options['from_planning_edit_ajax']) && $options['from_planning_edit_ajax']) {
         echo Html::hidden('from_planning_edit_ajax');
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Status')."</td>";
      echo "<td colspan='2'>";
      if ($canedit) {
         Planning::dropdownState("state", $this->fields["state"], true, [
            'rand' => $rand,
         ]);
      } else {
         echo Planning::getState($this->fields["state"]);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Category')."</td>";
      echo "<td colspan='2'>";
      if ($canedit) {
         PlanningEventCategory::dropdown([
            'value' => $this->fields['planningeventcategories_id'],
            'rand'  => $rand
         ]);
      } else {
         echo Dropdown::getDropdownName(
            PlanningEventCategory::getTable(),
            $this->fields['planningeventcategories_id']
         );
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Background event')."</td>";
      echo "<td colspan='2'>";
      if ($canedit) {
         Dropdown::showYesNo('background', $this->fields['background'], -1, [
            'rand' => $rand,
         ]);
      } else {
         echo Dropdown::getYesNo($this->fields['background']);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td  colspan='2'>".__('Calendar')."</td>";
      echo "<td>";
      Planning::showAddEventClassicForm([
         'items_id'  => $this->fields['id'],
         'itemtype'  => $this->getType(),
         'begin'     => $this->fields['begin'],
         'end'       => $this->fields['end'],
         'rand_user' => $this->fields['users_id'],
         'rand'      => $rand_plan,
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td  colspan='2'>".__('Repeat')."</td>";
      echo "<td>";
      echo self::showRepetitionForm($this->fields['rrule'], [
         'rand' => $rand_rrule
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".__('Description')."</td>".
           "<td colspan='3'>";

      if ($canedit) {
         Html::textarea([
            'name'              => 'text',
            'value'             => $this->fields["text"],
            'enable_richtext'   => true,
            'enable_fileupload' => true,
            'rand'              => $rand,
            'editor_id'         => 'text'.$rand,
         ]);
      } else {
         echo "<div>";
         echo Toolbox::unclean_html_cross_side_scripting_deep($this->fields["text"]);
         echo "</div>";
      }

      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }

   function getRights($interface = 'central') {
      $values = parent::getRights();

      $values[self::MANAGE_BG_EVENTS] = __('manage background events');

      return $values;
   }
}
