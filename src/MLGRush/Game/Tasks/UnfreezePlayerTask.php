<?php

namespace MLGRush\Game\Tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class UnfreezePlayerTask extends Task{
    public $player;

    public function __construct(Player $player) {
        $this->player = $player;
    }

    public function onRun(): void {
        if ($this->player != null && $this->player->isOnline() && !$this->player->isClosed()) {
            $this->player->setNoClientPredictions(false);
        }
    }
}