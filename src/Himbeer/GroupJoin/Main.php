<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use IvanCraft623\RankSystem\RankSystem;
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
		 * @var RankSystem|null $rankSystem
		 */
		$rankSystem = $this->getServer()->getPluginManager()->getPlugin("RankSystem");
		/**
		 * @var GroupSystem|null $groupSystem
		 */
		$groupSystem = $this->getServer()->getPluginManager()->getPlugin("GroupSystem");

		$installed_plugins = array_filter([$rankSystem, $groupSystem], function($p) {
			return $p !== null;
		});

		if (count($installed_plugins) > 1) {
			$this->getLogger()->error("Both RankSystem and GroupSystem are installed. You will need one of them (but not multiple at the same time) for this plugin to work!");
			throw new DisablePluginException();
		} else if ($groupSystem !== null) {
			$groupSystemVer = new VersionString($groupSystem->getDescription()->getVersion());
			if ($groupSystemVer->compare(new VersionString("3.2.3")) != 1) { // Installed version is greater than or equal to 3.2.3
				$this->getServer()->getPluginManager()->registerEvents(new GroupSystemListener($this), $this);
			} else {
				$this->getLogger()->error("GroupSystem version must be 3.2.3 or higher!");
				throw new DisablePluginException();
			}
		} else if ($rankSystem !== null) {
			$this->getServer()->getPluginManager()->registerEvents(new RankSystemListener($this, $rankSystem), $this);
		} else {
			$this->getLogger()->error("Neither RankSystem, nor GroupSystem are installed. You will need one of them (but not multiple at the same time) for this plugin to work!");
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
