<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use _64FF00\PurePerms\PurePerms;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class PurePermsListener implements Listener {
	/** @var Main */
	private $plugin;

	/** @var PurePerms */
	private $purePerms;

	public function __construct(Main $plugin, PurePerms $purePerms) {
		$this->plugin = $plugin;
		$this->purePerms = $purePerms;
	}

	private function getPurePermsGroupNameForPlayer(Player $player) : string {
		$ppGroup = $this->purePerms->getUserDataMgr()->getGroup($player);
		if ($ppGroup === null) {
			// This should never happen, if it does, the server owner messed up their PurePerms config
			// We don't need to log this, PurePerms does that already
			return "";
		}
		return $ppGroup->getName();
	}

	private function getPurePermsMessageForPlayer(Player $player, string $type) : ?string {
		$groupName = $this->getPurePermsGroupNameForPlayer($player);
		$msg = $this->plugin->getMessageForGroupName($groupName, $type, $player->getName());
		return $msg;
	}

	public function onJoinPurePerms(PlayerJoinEvent $event) {
		$message = $this->getPurePermsMessageForPlayer($event->getPlayer(), "join");
		if ($message !== null) {
			$event->setJoinMessage($message);
		} else if ($this->plugin->hideOther) {
			$event->setJoinMessage("");
		}
	}

	public function onQuitPurePerms(PlayerQuitEvent $event) {
		$message = $this->getPurePermsMessageForPlayer($event->getPlayer(), "leave");
		if ($message !== null) {
			$event->setQuitMessage($message);
		} else if ($this->plugin->hideOther) {
			$event->setQuitMessage("");
		}
	}
}