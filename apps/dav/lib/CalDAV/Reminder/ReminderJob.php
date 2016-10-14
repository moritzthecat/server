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

use OC\BackgroundJob\TimedJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Defaults;
use OCP\IConfig;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Util;

class ReminderJob extends TimedJob {

	/** @var IConfig  */
	private $config;

	/** @var Defaults  */
	private $defaults;

	/** @var IMailer */
	private $mailer;

	/** @var IL10N */
	private $l10n;

	/** @var IManager */
	private $notifications;

	/** @var CalDavBackend */
	private $backend;

	/** @var IUserManager */
	private $usermanager;

	public function __construct(IConfig $config, Defaults $defaults, IMailer $mailer, IL10N $l10n, IManager $notifications, CalDavBackend $backend, IUserManager $usermanager) {
		$this->config = $config;
		$this->defaults = $defaults;
		$this->mailer = $mailer;
		$this->l10n = $l10n;
		$this->notifications = $notifications;
		$this->backend = $backend;
		$this->usermanager = $usermanager;

		/** Run every 15 minutes */
		$this->setInterval(10);
	}

	/**
	 * @param $arg
	 */
	public function run($arg) {
		$reminders = $this->backend->getReminders();

		foreach ($reminders as $reminder) {
			if ($reminder['notificationDate'] > (new \DateTime())->getTimestamp()) {
				$calendar = $this->backend->getCalendarById($reminder['calendarId']);
				switch ($reminder['type']) {
					case 'EMAIL':
						$this->sendMail($this->usermanager->get($reminder['userid']), $calendar);
						break;
					case 'DISPLAY':
						$this->sendNotification($this->usermanager->get($reminder['userid']), $reminder['notificationDate'], $calendar);
						break;
				}
				$this->backend->removeReminder($reminder['id']);
			}
		}
	}

	private function sendMail(IUser $user, array $calendar) {
		$message = $this->mailer->createMessage();
		$message->setSubject($this->l10n->t('Your Subject'));

		$from = Util::getDefaultEmailAddress('register');

		$message->setFrom([$from => $this->defaults->getName()]);
		$message->setTo([$user->getEMailAddress() => 'Recipient']);
		$message->setBody($calendar['calendardata'], 'text/calendar; charset=UTF-8');
		$this->mailer->send($message);
	}

	private function sendNotification(IUser $user, \DateTime $time, $calendar) {
		/** @var INotification $notification */
		$notification = $this->notifications->createNotification();
		$notification->setApp('dav')
			->setUser($user->getUID())
			->setDateTime($time)
			->setObject('calendar_reminder', $calendar['id']) // $type and $id
			->setSubject('calendar_reminder', [$calendar]) // $subject and $parameters
		;
		$this->notifications->notify($notification);
	}
}