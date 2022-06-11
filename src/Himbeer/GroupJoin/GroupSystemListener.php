<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use r3pt1s\GroupSystem\GroupSystem;

class GroupSystemListener implements Listener {
	private Main $plugin;

	private GroupSystem $groupSystem;

	public function __construct(Main $plugin, GroupSystem $groupSystem) {
		$this->plugin = $plugin;
		$this->groupSystem = $groupSystem;
	}

	private function getGroupNameForPlayer(Player $player) : string {
		return $this->groupSystem->getPlayerGroupManager()->getGroup($player->getName())->getGroup()->getName();
	}

	private function getMessageForPlayer(Player $player, string $type) : ?string {
		$groupName = $this->getGroupNameForPlayer($player);
		return $this->plugin->getMessageForGroupName($groupName, $type, $player->getName());
	}

	public function onJoin(PlayerJoinEvent $event) {
		$message = $this->getMessageForPlayer($event->getPlayer(), "join");
		if ($message !== null) {
			$event->setJoinMessage($message);
		} else if ($this->plugin->hideOther) {
			$event->setJoinMessage("");
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		$message = $this->getMessageForPlayer($event->getPlayer(), "leave");
		if ($message !== null) {
			$event->setQuitMessage($message);
		} else if ($this->plugin->hideOther) {
			$event->setQuitMessage("");
		}
	}
}