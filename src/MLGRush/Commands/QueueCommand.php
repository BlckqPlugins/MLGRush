<?php

namespace MLGRush\Commands;

use MLGRush\Game\GameManager;
use MLGRush\Lobby\LobbyMain;
use MLGRush\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class QueueCommand extends Command
{

    public function __construct()
    {
        parent::__construct("queue", "Queue Command", "/queue", ["q"]);
        $this->setPermission("mlg.command.queue");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {

            if (LobbyMain::get()->isInLobby($sender)) {
                if (!LobbyMain::get()->isInQueue($sender)) {
                    LobbyMain::get()->addToQueue($sender);
                    $sender->sendMessage(Main::getInstance()->prefix . "§aYou have joined the queue.");
                } else {
                    if (LobbyMain::get()->isInQueue($sender)) {
                        LobbyMain::get()->removeFromQueue($sender);
                        $sender->sendMessage(Main::getInstance()->prefix . "§cYou have left the queue.");
                    }
                }
            } else {
                $sender->sendMessage(Main::getInstance()->prefix . "§cYou can't join the queue.");
            }
        }
    }
}