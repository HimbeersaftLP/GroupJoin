<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use _64FF00\PurePerms\PurePerms;
use alvin0319\GroupsAPI\GroupsAPI;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\VersionString;
use r3pt1s\GroupSystem\GroupSystem;

class Main extends PluginBase {
	public bool $hideOther;

	/** @var string[][] */
	private array $messages;

	public function onEnable() : void {
		$this->saveDefaultConfig();
		$this->hideOther = (bool) $this->getConfig()->get("hide-other");
		$this->messages = $this->getConfig()->get("groups");

		/**
		 * @var PurePerms|null $purePerms
		 */
		$purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		/**
		 * @var GroupsAPI|null $groupsAPI
		 */
		$groupsAPI = $this->getServer()->getPluginManager()->getPlugin("GroupsAPI");
		/**
		 * @var GroupSystem|null $groupSystem
		 */
		$groupSystem = $this->getServer()->getPluginManager()->getPlugin("GroupSystem");

		$installed_plugins = array_filter([$purePerms, $groupsAPI, $groupSystem], function($p) {
			return $p !== null;
		});

		if (count($installed_plugins) > 1) {
			$this->getLogger()->error("Two or more of PurePerms, GroupsAPI and GroupSystem are installed. You will need one of them (but not multiple at the same time) for this plugin to work!");
			throw new DisablePluginException();
		} else if ($purePerms !== null) {
			$this->getServer()->getPluginManager()->registerEvents(new PurePermsListener($this, $purePerms), $this);
		} else if ($groupsAPI !== null) {
			$groupsAPIVer = new VersionString($groupsAPI->getDescription()->getVersion());
			if ($groupsAPIVer->getMajor() >= 2) {
				$this->getServer()->getPluginManager()->registerEvents(new GroupsAPIListener($this, $groupsAPI), $this);
			} else {
				$this->getLogger()->error("GroupsAPI version must be 2.0.0 or higher!");
				throw new DisablePluginException();
			}
		} else if ($groupSystem !== null) {
			$this->getServer()->getPluginManager()->registerEvents(new GroupSystemListener($this, $groupSystem), $this);
		} else {
			$this->getLogger()->error("Neither PurePerms, nor GroupsAPI, nor GroupSystem are installed. You will need one of them (but not multiple at the same time) for this plugin to work!");
			throw new DisablePluginException();
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
