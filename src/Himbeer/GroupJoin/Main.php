<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use _64FF00\PurePerms\PurePerms;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	/** @var bool */
	private $hideOther;

	/** @var string[][] */
	private $messages;

	/** @var PurePerms */
	private $purePerms;

	public function onEnable(): void {
		$this->saveDefaultConfig();
		$this->hideOther = (bool)$this->getConfig()->get("hide-other");
		$this->messages = $this->getConfig()->get("groups");

		$this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	private function getGroupNameForPlayer(Player $player): string {
		$ppGroup = $this->purePerms->getUserDataMgr()->getGroup($player);
		if ($ppGroup === null) {
			// This should never happen, if it does, the server owner messed up their PurePerms config
			// We don't need to log this, PurePerms does that already
			return "";
		}
		return $ppGroup->getName();
	}

	private function getMessageForPlayer(Player $player, string $type): ?string {
		$groupName = $this->getGroupNameForPlayer($player);
		if (!isset($this->messages[$groupName])) {
			return null;
		}
		$messages = $this->messages[$groupName];
		if (!isset($messages[$type])) {
			return null;
		}
		return str_replace("{player}", $player->getName(), $messages[$type]);
	}

	public function onJoin(PlayerJoinEvent $event) {
		$message = $this->getMessageForPlayer($event->getPlayer(), "join");
		if ($message) {
			$event->setJoinMessage($message);
		} else if ($this->hideOther) {
			$event->setJoinMessage("");
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		$message = $this->getMessageForPlayer($event->getPlayer(), "leave");
		if ($message) {
			$event->setQuitMessage($message);
		} else if ($this->hideOther) {
			$event->setQuitMessage("");
		}
	}
}
