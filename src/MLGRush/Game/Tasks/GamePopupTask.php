<?php
namespace MLGRush\Game\Tasks;

use MLGRush\Game\Game;
use MLGRush\Game\GameManager;
use pocketmine\scheduler\Task;

class GamePopupTask extends Task{
    public function __construct() {
    }

    public function onRun(): void {
        $gm = GameManager::get();

		foreach ($gm->games as $game) {
			if ($game instanceof Game) {
				$started = $game->started;
				$length = gmdate("H:i:s", time() -$started);
				$game->broadcastPopup("§8[§e{$length}§8]");
			}
		}
    }
}