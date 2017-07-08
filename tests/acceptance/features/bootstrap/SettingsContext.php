<?php

/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Behat\Behat\Context\Context;

class SettingsContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function systemTagsSelectTagButton() {
		return Locator::forThe()->id("s2id_systemtag")->
				describedAs("Select tag button in system tags section in Administration Settings");
	}

	/**
	 * @return Locator
	 */
	public static function systemTagsItemInDropdownForTag($tag) {
		return Locator::forThe()->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' select2-result-label ')]//span[normalize-space() = '$tag']/ancestor::li")->
				descendantOf(self::select2Dropdown())->
				describedAs("Item in dropdown for tag $tag in system tags section in Administration Settings");
	}

	/**
	 * @return Locator
	 */
	private static function select2Dropdown() {
		return Locator::forThe()->css("#select2-drop")->
				describedAs("Select2 dropdown in Settings");
	}

	/**
	 * @return Locator
	 */
	private static function select2DropdownMask() {
		return Locator::forThe()->css("#select2-drop-mask")->
				describedAs("Select2 dropdown mask in Settings");
	}

	/**
	 * @return Locator
	 */
	public static function systemTagsTagNameInput() {
		return Locator::forThe()->id("systemtag_name")->
				describedAs("Tag name input in system tags section in Administration Settings");
	}

	/**
	 * @return Locator
	 */
	public static function systemTagsCreateOrUpdateButton() {
		return Locator::forThe()->id("systemtag_submit")->
				describedAs("Create/Update button in system tags section in Administration Settings");
	}

	/**
	 * @return Locator
	 */
	public static function systemTagsResetButton() {
		return Locator::forThe()->id("systemtag_reset")->
				describedAs("Reset button in system tags section in Administration Settings");
	}

	/**
	 * @When I create the tag :tag in the settings
	 */
	public function iCreateTheTagInTheSettings($tag) {
		$this->actor->find(self::systemTagsResetButton(), 10)->click();
		$this->actor->find(self::systemTagsTagNameInput())->setValue($tag);
		$this->actor->find(self::systemTagsCreateOrUpdateButton())->click();
	}

	/**
	 * @Then I see that the dropdown for tags in the settings eventually contains the tag :tag
	 */
	public function iSeeThatTheDropdownForTagsInTheSettingsEventuallyContainsTheTag($tag) {
		// When the dropdown is opened it is not automatically updated if new
		// tags are added to the server, and when a tag is created, no explicit
		// feedback is provided to the user about the completion of that
		// operation (that is, when the tag is added to the server). Therefore,
		// to verify that creating a tag does in fact add it to the server it is
		// necessary to repeatedly open the dropdown until the tag is shown in
		// the dropdown (or the timeout expires).

		$actor = $this->actor;

		$tagFoundInDropdownCallback = function() use($actor, $tag) {
			// Open the dropdown to look for the tag.
			$actor->find(self::systemTagsSelectTagButton(), 10)->click();

			PHPUnit_Framework_Assert::assertTrue($this->actor->find(self::select2Dropdown(), 10)->isVisible());

			try {
				$tagFound = $this->actor->find(self::systemTagsItemInDropdownForTag($tag))->isVisible();
			} catch (NoSuchElementException $exception) {
				$tagFound = false;
			}

			// Close again the dropdown after looking for the tag. When a
			// dropdown is opened Select2 creates a special element that masks
			// every other element but the dropdown to get all mouse clicks;
			// this is used by Select2 to close the dropdown when the user
			// clicks outside it.
			$actor->find(self::select2DropdownMask())->click();

			return $tagFound;
		};

		if (!Utils::waitFor($tagFoundInDropdownCallback, $timeout = 10, $timeoutStep = 1)) {
			PHPUnit_Framework_Assert::fail("The dropdown in system tags section in Administration Settings does not contain the tag $tag after $timeout seconds");
		}
	}

}
