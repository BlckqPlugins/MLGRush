<?php

namespace MLGRush\Lobby\Tasks;

use MLGRush\Main;
use pocketmine\scheduler\Task;

class ResetCooldownTask extends Task {

    protected string $name;

    public function __construct($name){
        $this->name = $name;
    }

   public function onRun(): void
   {
       unset(Main::getInstance()->hitDelay[array_search($this->name, Main::getInstance()->hitDelay)]);
   }

}