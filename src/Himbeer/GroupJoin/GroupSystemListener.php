<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\session\Session;

class GroupSystemListener implements Listener {
	private Main $plugin;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param Player   $player
	 * @param callable $callback Callback with the group name
	 */
	private function getGroupNameForPlayer(Player $player, callable $callback) : void {
		Session::get($player)->onLoad(function(PlayerGroup $currentGroup, array $groups, array $permissions) use ($callback) {
			$callback($currentGroup->getGroup()->getName());
		});
	}

	private function getAndSendMessageForPlayer(Player $player, string $type, $originalMessage) {
		$this->getGroupNameForPlayer($player, function(string $groupName) use ($type, $originalMessage, $player) {
			$msg = $this->plugin->getMessageForGroupName($groupName, $type, $player->getName());
			if ($msg === null) {
				if (!$this->plugin->hideOther) {
					$this->plugin->getServer()->broadcastMessage($originalMessage);
				}
			} else {
				$this->plugin->getServer()->broadcastMessage($msg);
			}
		});
	}

	public function onJoin(PlayerJoinEvent $event) {
		$this->getAndSendMessageForPlayer($event->getPlayer(), "join", $event->getJoinMessage());
		$event->setJoinMessage("");
	}

	public function onQuit(PlayerQuitEvent $event) {
		$this->getAndSendMessageForPlayer($event->getPlayer(), "leave", $event->getQuitMessage());
		$event->setQuitMessage("");
	}
}