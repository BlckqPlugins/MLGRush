<?php
namespace MLGRush\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use MLGRush\Game\GameManager;


class LeaveCommand extends Command {

    public function __construct()
    {
        parent::__construct("leave", "Leave Command", "/leave", ["quit"]);
        $this->setPermission("mlg.command.leave");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
    	if ($sender instanceof Player) {
			GameManager::get()->leave($sender);
		}
    }
}