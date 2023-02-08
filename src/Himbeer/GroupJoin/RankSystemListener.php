<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use IvanCraft623\RankSystem\rank\Rank;
use IvanCraft623\RankSystem\RankSystem;
use IvanCraft623\RankSystem\session\Session;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class RankSystemListener implements Listener {
	private Main $plugin;

	private RankSystem $rankSystem;

	public function __construct(Main $plugin, RankSystem $rankSystem) {
		$this->plugin = $plugin;
		$this->rankSystem = $rankSystem;
	}

	/**
	 * @param Session $session
	 *
	 * @return string[]
	 */
	private static function getSessionGroupNames(Session $session) : array {
		return array_map(function(Rank $rank) {
			return $rank->getName();
		}, $session->getRanks());
	}

	/**
	 * @param Player   $player
	 * @param callable $callback Callback with the group names in an array as the first parameter
	 */
	private function getGroupNamesForPlayer(Player $player, callable $callback) {
		$session = $this->rankSystem->getSessionManager()->get($player);
		$session->onInitialize(function() use ($session, $callback) {
			$callback(self::getSessionGroupNames($session));
		});
	}

	private function getAndSendMessageForPlayer(Player $player, string $type, $originalMessage) {
		$this->getGroupNamesForPlayer($player, function(array $groupNames) use ($type, $originalMessage, $player) {
			if (count($groupNames) === 0) {
				$this->plugin->getServer()->broadcastMessage($originalMessage);
			} else {
				$msg = $this->plugin->getMessageForGroupNames($groupNames, $type, $player->getName());
				if ($msg === null) {
					if (!$this->plugin->hideOther) {
						$this->plugin->getServer()->broadcastMessage($originalMessage);
					}
				} else {
					$this->plugin->getServer()->broadcastMessage($msg);
				}
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