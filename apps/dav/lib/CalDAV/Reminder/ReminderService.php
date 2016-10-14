<?php
/**
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\CalDAV\Reminder;

use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\VObject;
use Sabre\VObject\Component\VAlarm;

class ReminderService {

	/** @var CalDavBackend  */
	private $calDavBackEnd;

	private $types = ['AUDIO', 'EMAIL', 'DISPLAY'];

	/**
	 * BirthdayService constructor.
	 *
	 * @param CalDavBackend $calDavBackEnd
	 */
	public function __construct(CalDavBackend $calDavBackEnd) {
		$this->calDavBackEnd = $calDavBackEnd;
	}

	/**
	 * @param $calendarId
	 * @param $objectUri
	 * @param $calendarData
	 */
	function onCalendarObjectChanged($calendarId, $objectUri, $calendarData) {
		$vobject = VObject\Reader::read($calendarData);

		/** Remove all other reminders for this event */
		$this->calDavBackEnd->cleanRemindersForEvent($calendarId, $objectUri);

		foreach ($vobject->VEVENT->VALARM as $alarm) {
			if ($alarm instanceof VAlarm) {
				$type = strtoupper($alarm->ACTION->getValue());
				if (in_array($type, $this->types)) {
					/** @var \DateTime $time */
					$time = $alarm->getEffectiveTriggerTime();
					$this->calDavBackEnd->addReminderForEvent($calendarId, $objectUri, $type, $time);
				}
			}
		}
	}

	function onCalendarObjectDeleted($calendarId, $objectUri) {
		$this->calDavBackEnd->cleanRemindersForEvent($calendarId, $objectUri);
	}

}