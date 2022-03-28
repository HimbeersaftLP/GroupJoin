<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use _64FF00\PurePerms\PurePerms;
use alvin0319\GroupsAPI\GroupsAPI;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {
	public bool $hideOther;

	/** @var string[][] */
	private array $messages;

	public function onEnable() : void {
		$this->saveDefaultConfig();
		$this->hideOther = (bool) $this->getConfig()->get("hide-other");
		$this->messages = $this->getConfig()->get("groups");

		/**
		 * @var PurePerms $purePerms
		 */
		$purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		/**
		 * @var GroupsAPI $groupsAPI
		 */
		$groupsAPI = $this->getServer()->getPluginManager()->getPlugin("GroupsAPI");

		if ($purePerms !== null && $groupsAPI !== null) {
			$this->getLogger()->error("Both PurePerms and GroupsAPI are installed. You will need either one (but not both at the same time) for this plugin to work!");
		} else if ($purePerms !== null) {
			$this->getServer()->getPluginManager()->registerEvents(new PurePermsListener($this, $purePerms), $this);
		} else if ($groupsAPI !== null) {
			$this->getServer()->getPluginManager()->registerEvents(new GroupsAPIListener($this, $groupsAPI), $this);
		} else {
			$this->getLogger()->error("Neither PurePerms nor GroupsAPI are installed. You will need either one (but not both at the same time) for this plugin to work!");
		}
	}

	private static function doStringReplacement(string $str, string $playerName) : string {
		return str_replace("{player}", $playerName, $str);
	}

	public function getMessageForGroupName(string $groupName, string $type, string $playerName) : ?string {
		if (!isset($this->messages[$groupName])) {
			return null;
		}
		$messages = $this->messages[$groupName];
		if (!isset($messages[$type])) {
			return null;
		}
		return self::doStringReplacement($messages[$type], $playerName);
	}

	public function getMessageForGroupNames(array $groupNames, string $type, string $playerName) : ?string {
		foreach ($this->messages as $msgGroupName => $messages) {
			foreach ($groupNames as $groupName) {
				if ($msgGroupName === $groupName) {
					if (isset($messages[$type])) {
						return self::doStringReplacement($messages[$type], $playerName);
					}
				}
			}
		}
		return null;
	}
}
