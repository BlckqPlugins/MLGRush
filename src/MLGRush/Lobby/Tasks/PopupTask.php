<?php
namespace MLGRush\Lobby\Tasks;

use MLGRush\Lobby\LobbyMain;
use MLGRush\Main;
use pocketmine\scheduler\Task;

class PopupTask extends Task
{
    public function onRun(): void {
        $lobby = LobbyMain::get();
		$playernames = $lobby->getPlayersInLobby();

		foreach ($playernames as $playername) {
        	$player = Main::getInstance()->getServer()->getPlayerExact($playername);

            if ($lobby->isInQueue($player)) {
				$player->sendPopup("§r§fWaiting for an enemy...");
            }
        }
    }
}