<?php
namespace MLGRush\Lobby\Tasks;

use MLGRush\Game\GameManager;
use MLGRush\Lobby\LobbyMain;
use MLGRush\Main;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;


class QueueTask extends Task{
	public function onRun(): void{

        if (isset(LobbyMain::get()->queue[0])){
            $red = Main::getInstance()->getServer()->getPlayerExact(LobbyMain::get()->queue[0]);
            $red?->sendActionBarMessage("§cWaiting for players...");
        }

        if (isset(LobbyMain::get()->queue[1])){
            $blue = Main::getInstance()->getServer()->getPlayerExact(LobbyMain::get()->queue[1]);
            $blue?->sendActionBarMessage("§cWaiting for players...");
        }

		if (count(LobbyMain::get()->queue) > 1) {
			$red = Main::getInstance()->getServer()->getPlayerExact(LobbyMain::get()->queue[0]);
			$blue = Main::getInstance()->getServer()->getPlayerExact(LobbyMain::get()->queue[1]);
			if ($red instanceof Player && $blue instanceof Player) {
                GameManager::$arenaGenerator->startMatch($red, $blue);
                LobbyMain::get()->removeFromQueue($red);
                LobbyMain::get()->removeFromQueue($blue);
            }
		}
	}
}