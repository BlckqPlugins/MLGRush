<?php

namespace MLGRush\Game\Tasks;

use MLGRush\Game\Game;
use MLGRush\Game\GameManager;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class CheckPlayersTask extends Task{
    public $manager;
    
    public function __construct(GameManager $manager) {
        $this->manager = $manager;
    }
    
    public function onRun(): void {
        /** @var Game $game */
        foreach ($this->manager->games as $game) {
            $min = $game->minheight;

            if (!$game->redplayer instanceof Player){
                return;
            }

            if (!$game->blueplayer instanceof Player){
                return;
            }

            if($game->redplayer->getPosition()->asVector3()->getY() < $min){
                $game->playerFell($game->redplayer);
            }
            if($game->blueplayer->getPosition()->asVector3()->getY() < $min){
                $game->playerFell($game->blueplayer);
            }
            $game->redplayer->setHealth(20.0);
            $game->blueplayer->setHealth(20.0);
        }
    }
}