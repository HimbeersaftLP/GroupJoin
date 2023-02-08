<?php

declare(strict_types=1);

namespace Himbeer\GroupJoin;

use alvin0319\GroupsAPI\event\MemberLoadEvent;
use alvin0319\GroupsAPI\group\GroupWrapper;
use alvin0319\GroupsAPI\GroupsAPI;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class GroupsAPIListener implements Listener {
	private Main $plugin;

	private GroupsAPI $groupsAPI;
	/**
	 * @var string[]
	 */
	private array $originalJoinMessages = [];

	public function __construct(Main $plugin, GroupsAPI $groupsAPI) {
		$this->plugin = $plugin;
		$this->groupsAPI = $groupsAPI;
	}

	/**
	 * @param Player $player
	 */
	private function getGroupNamesForPlayer(Player $player) {
		$member = $this->groupsAPI->getMemberManager()->getMember($player->getName());
		if ($member === null) {
			return [];
		}
		return array_map(function(GroupWrapper $groupWrapper) {
			return $groupWrapper->getGroup()->getName();
		}, $member->getGroups()
		);
	}

	private function getAndSendMessageForPlayer(Player $player, string $type, $originalMessage) {
		$groupNames = $this->getGroupNamesForPlayer($player);
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
	}

	public function onJoin(PlayerJoinEvent $event) {
		$this->originalJoinMessages[$event->getPlayer()->getName()] = $event->getJoinMessage();
		$event->setJoinMessage("");
	}

	// We have to wait for GroupsAPI to load the Member because it's not guaranteed that the player is already cached on join
	public function onMemberLoad(MemberLoadEvent $event) {
		if (isset($this->originalJoinMessages[$event->getPlayer()->getName()])) {
			$originalJoinMessage = $this->originalJoinMessages[$event->getPlayer()->getName()];
			unset($this->originalJoinMessages[$event->getPlayer()->getName()]);
			$this->getAndSendMessageForPlayer($event->getPlayer(), "join", $originalJoinMessage);
		} else {
			$this->plugin->getLogger()->debug("No original join message captured for {$event->getPlayer()->getName()}");
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		// In case the player leaves before the Member was loaded by GroupsAPI or if the loading fails for some reason
		if (isset($this->originalJoinMessages[$event->getPlayer()->getName()])) {
			unset($this->originalJoinMessages[$event->getPlayer()->getName()]);
		}
		$this->getAndSendMessageForPlayer($event->getPlayer(), "leave", $event->getQuitMessage());
		$event->setQuitMessage("");
	}
}