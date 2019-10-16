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

abstract class AbstractPlanningEvent extends \DbTestCase {
   protected $myclass  = "";
   protected $input    = [];

   private $begin    = "";
   private $end      = "";
   private $duration = "";

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);

      $now            = time();
      $this->duration = 2 * \HOUR_TIMESTAMP;
      $this->begin    = date('Y-m-d H:i:s', $now);
      $this->end      = date('Y-m-d H:i:s', $now + $this->duration);

      $this->input = [
         'name'       => 'test add external event',
         'test'       => 'comment for external event',
         'plan'       => [
            'begin'     => $this->begin,
            '_duration' => $this->duration,
         ],
         'rrule'      => [
            'freq'      => 'daily',
            'interval'  => 1,
            'byweekday' => 'MO',
            'bymonth'   => 1,
         ],
         'state'      => \Planning::TODO,
         'background' => 1,
      ];
   }

   public function testAdd() {
      $event = new $this->myclass;
      $id    = $event->add($this->input);

      $this->integer((int) $id)->isGreaterThan(0);
      $this->boolean($event->getFromDB($id))->isTrue();

      // check end date
      if (isset($event->fields['end'])) {
         $this->string($event->fields['end'])->isEqualTo($this->end);
      }

      // check rrule encoding
      $this->string($event->fields['rrule'])
           ->isEqualTo('{"freq":"daily","interval":1,"byweekday":"MO","bymonth":1}');

      return $event;
   }


   public function testUpdate() {
      $this->login();

      $event = new $this->myclass;
      $id    = $event->add($this->input);

      $new_begin = date("Y-m-d H:i:s", strtotime($this->begin) + $this->duration);
      $new_end   = date("Y-m-d H:i:s", strtotime($this->end) + $this->duration);

      $update = array_merge($this->input, [
         'id'         => $id,
         'name'       => 'updated external event',
         'test'       => 'updated comment for external event',
         'plan'       => [
            'begin'     => $new_begin,
            '_duration' => $this->duration,
         ],
         'rrule'      => [
            'freq'      => 'monthly',
            'interval'  => 2,
            'byweekday' => 'TU',
            'bymonth'   => 2,
         ],
         'state'      => \Planning::INFO,
         'background' => 0,
      ]);
      $this->boolean($event->update($update))->isTrue();

      // check dates (we added duration to both dates on update)
      if (isset($event->fields['begin'])) {
         $this->string($event->fields['begin'])
            ->isEqualTo($new_begin);
      }
      if (isset($event->fields['end'])) {
         $this->string($event->fields['end'])
            ->isEqualTo($new_end);
      }

      // check rrule encoding
      $this->string($event->fields['rrule'])
           ->isEqualTo('{"freq":"monthly","interval":2,"byweekday":"TU","bymonth":2}');
   }


   public function testDelete() {
      $event = new $this->myclass;
      $id    = $event->add($this->input);

      $this->boolean($event->delete([
         'id' => $id,
      ]))->isTrue();
   }
}
