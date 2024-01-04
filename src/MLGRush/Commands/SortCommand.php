<?php

namespace MLGRush\Commands;

use Frago9876543210\EasyForms\elements\Slider;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use MLGRush\Database\StatsAPI;
use MLGRush\Lobby\LobbyMain;
use MLGRush\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SortCommand extends Command {

    public function __construct()
    {
        parent::__construct("axosort", "Sort your inventory", "/axosort", ["axos"]);
        $this->setPermission("mlg.command.sort");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if ($sender instanceof Player) {

            if (LobbyMain::get()->isInLobby($sender)) {
                $db = StatsAPI::get();
                $slots = $db->getSlots($sender);
                $sender->sendForm(new CustomForm(
                    "§dSort Inventory",
                    [
                        new Slider("Stick", 1, 9, 1, $slots["stick"]),
                        new Slider("Pickaxe", 1, 9, 1, $slots["pickaxe"]),
                        new Slider("Blocks", 1, 9, 1, $slots["blocks"])
                    ],
                    function (Player $player, CustomFormResponse $response) use ($db): void {
                        /** @var Slider[] $elements */
                        $elements = $response->getElements();

                        $stick = $elements[0]->getValue();
                        $pickaxe = $elements[1]->getValue();
                        $blocks = $elements[2]->getValue();
                        $prefix = LobbyMain::get()->plugin->prefix;

                        if (count(array_unique([$stick, $pickaxe, $blocks])) == 3) {
                            $db->setSlots($player, $stick, $pickaxe, $blocks);
                            $player->sendMessage("§aYour inventory was saved.");
                        } else {
                            $player->sendMessage("§cYour inventory wasn't saved.");
                        }
                    }
                ));
            } else {
                $sender->sendMessage(Main::getInstance()->prefix . "§cYou can't sort your inventory now.");
            }
        }
    }

}