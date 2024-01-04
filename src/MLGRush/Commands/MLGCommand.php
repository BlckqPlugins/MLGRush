<?php

namespace MLGRush\Commands;

use MLGRush\Game\GameManager;
use MLGRush\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;

class MLGCommand extends Command {

    public function __construct()
    {
        parent::__construct("mlg", "MLGRush Command", "/mlg", ["mlgrush"]);
        $this->setPermission("mlg.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        
        if (!$this->testPermission($sender)){
            $sender->sendMessage("No perms!");
            return false;
        }
        
        if ($sender instanceof Player) {
            if(isset($args[0])){
                $cmd = strtolower($args[0]);
                switch ($cmd) {
                    case "make":
                    case "create":
                    case "regarena":
                        $this->regArena($sender, $args);
                        break;
                    case "spawn":
                    case "setpos":
                    case "setspawn":
                        $this->setSpawn($sender, $args);
                        break;
                    case "setmaxheight":
                        $this->setMaxHeight($sender, $args);
                        break;
                    case "setminheight":
                        $this->setMinHeight($sender, $args);
                        break;
                    default:
                        $this->sendHelp($sender);
                }
            }else{
                $this->sendHelp($sender);
            }
        }
        return true;
    }

    public function regArena(Player $player, array $args): void {
        if (!$player->hasPermission("mlg.admin")) {
            return;
        }
        if (isset($args[1])) {
            $path = Server::getInstance()->getDataPath() . "/worlds/" . $args[1] . "/";
            if (is_dir($path)) {
                new Config(Main::getInstance()->_getDataFolder_() . "/arenas/" . $args[1] . ".yml", Config::YAML, [
                    "spawnred" => [
                        1,
                        1,
                        1
                    ],
                    "spawnblue" => [
                        2,
						2,
                        2
                    ],
                    "maxheighty" => 20,
                    "minheighty" => 0
                ]);
                $player->sendMessage(Main::getInstance()->prefix . "Config file for " . $args[1] . " created!");
                Server::getInstance()->getWorldManager()->loadWorld($args[1], true);
                $player->teleport(Server::getInstance()->getWorldManager()->getWorldByName($args[1])->getSafeSpawn());
                $player->setGamemode(GameMode::CREATIVE());
            } else {
                $player->sendMessage(Main::getInstance()->prefix . "This world don't exists!");
            }
        } else {
            $player->sendMessage(Main::getInstance()->prefix . "Syntax: /mlgrush regarena <Worldname>");
        }
    }

    public function setSpawn(Player $player, array $args): void {
        if (!$player->hasPermission("mlg.admin")) {
            return;
        }
        if (isset($args[1]) && isset($args[2])) {
            $cfgpath = Main::getInstance()->_getDataFolder_() . "/arenas/" . $args[1] . ".yml";
            if (!file_exists($cfgpath)) {
                $player->sendMessage(Main::getInstance()->prefix . "No config file found!");
                return;
            }
            $cfg = new Config($cfgpath);
            switch ($args[2]) {
                case "red":
                case "rot":
                    $spawn = "spawnred";
                    break;
                case "blue":
                case "blau":
                    $spawn = "spawnblue";
                    break;
                default:
                    $player->sendMessage(Main::getInstance()->prefix . "Syntax: /mlgrush setspawn <Worldname> <red|blue>");
                    return;
            }
            $pos = [
                $player->getPosition()->getX(),
                $player->getPosition()->getY()+0.5,
                $player->getPosition()->getZ()
            ];
            $cfg->set($spawn, $pos);
            $cfg->save();
            $player->sendMessage(Main::getInstance()->prefix . "Spawn saved!");
        } else {
            $player->sendMessage(Main::getInstance()->prefix . "Syntax: /mlgrush setspawn <Worldname> <red|blue>");
        }
    }

    public function setMaxHeight(Player $player, array $args): void {
        if (!$player->hasPermission("mlg.admin")) {
            return;
        }
		if (isset($args[1]) && empty($args[2])) {
			if(ctype_digit($player->getPosition()->getY())){
                $cfgpath = Main::getInstance()->_getDataFolder_() . "/arenas/" . $args[1] . ".yml";
                if (!file_exists($cfgpath)) {
                    $player->sendMessage(Main::getInstance()->prefix . "No config file found!");
                    return;
                }
                $cfg = new Config($cfgpath);
                $cfg->set("maxheighty", (int)$player->getPosition()->getY());
                $cfg->save();

                $player->sendMessage(Main::getInstance()->prefix . "Saved height!");
                return;
            }
		} else if (isset($args[1]) && isset($args[2])) {
            if(ctype_digit($args[2])){
                $cfgpath = Main::getInstance()->_getDataFolder_() . "/arenas/" . $args[1] . ".yml";
                if (!file_exists($cfgpath)) {
                    $player->sendMessage(Main::getInstance()->prefix . "No config file found!");
                    return;
                }
                $cfg = new Config($cfgpath);
                $cfg->set("maxheighty", (int)$args[2]);
                $cfg->save();

                $player->sendMessage(Main::getInstance()->prefix . "Saved height!");
                return;
            }
        } else {
			$player->sendMessage(Main::getInstance()->prefix . "Syntax: /mlgrush setmaxheight <Weltname> [y-high]");
		}
    }

    public function setMinHeight(Player $player, array $args): void {
        if (!$player->hasPermission("mlg.admin")) {
            return;
        }
		if (isset($args[1]) && empty($args[2])) {
			if(ctype_digit($player->getPosition()->getY())){
                $cfgpath = Main::getInstance()->_getDataFolder_() . "/arenas/" . $args[1] . ".yml";
                if (!file_exists($cfgpath)) {
                    $player->sendMessage(Main::getInstance()->prefix . "No config file found!");
                    return;
                }
                $cfg = new Config($cfgpath);
                $cfg->set("minheighty", (int)$player->getPosition()->getY());
                $cfg->save();

                $player->sendMessage(Main::getInstance()->prefix . "Height saved!");
                return;
            }
		} else if (isset($args[1]) && isset($args[2])) {
            if(ctype_digit($args[2])){
                $cfgpath = Main::getInstance()->_getDataFolder_() . "/arenas/" . $args[1] . ".yml";
                if (!file_exists($cfgpath)) {
                    $player->sendMessage(Main::getInstance()->prefix . "No config file found!");
                    return;
                }
                $cfg = new Config($cfgpath);
                $cfg->set("minheighty", (int)$args[2]);
                $cfg->save();

                $player->sendMessage(Main::getInstance()->prefix . "Height saved!");
                return;
            }
        } else {
			$player->sendMessage(Main::getInstance()->prefix . "Syntax: /mlgrush setminheight <Weltname> [y-high]");
		}
    }

    public function sendHelp(Player $player):void{
        $player->sendMessage(Main::getInstance()->prefix."/mlgrush <create|setspawn|setminheight|setmaxheight>");
    }
}