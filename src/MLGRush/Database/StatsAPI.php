<?php
namespace MLGRush\Database;

use MLGRush\Main;
use MLGRush\player\PlayerSession;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class StatsAPI {
	/** @var StatsAPI */
	public static $self;
	public $plugin;

    /**
     * StatsAPI constructor.
     */
    public function __construct(){}

    public function load(): void{
        self::$self = $this;
    }

	public static function get(): self{
		return self::$self;
	}

	public function isPlayerInitialized(Player $player): bool{
		return !file_exists(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml");
	}

	public function initializePlayer(Player $player): void{
        Main::$players[$player->getName()] = new PlayerSession($player);

		if (!$this->isPlayerInitialized($player)) {
            $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
            $config->set("rounds", 0);
            $config->set("kills", 0);
            $config->set("deaths", 0);
            $config->set("wins", 0);
            $config->set("defeats", 0);
            $config->set("stick", 1);
            $config->set("pickaxe", 2);
            $config->set("blocks", 3);
            $config->set("sticktype", VanillaItems::STICK()->getTypeId());
            $config->set("blocktype", VanillaBlocks::SANDSTONE()->asItem()->getTypeId());
            $config->save();
        }
	}

	/** @param Player[] $players */
	public function addRound(array $players): void{
		foreach ($players as $player) {
            $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
            $config->set("rounds", $config->get("rounds") +1);
            $config->save();
		}
	}

	public function addKills(string $name, int $kills): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$name . ".yml", Config::YAML);
        $config->set("kills", $config->get("kills") +$kills);
        $config->save();
	}

	public function addDeaths(string $name, int $deaths): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$name . ".yml", Config::YAML);
        $config->set("deaths", $config->get("deaths") +$deaths);
        $config->save();
	}

	public function addBeds(Player $player, int $beds): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("beds", $config->get("beds") +$beds);
        $config->save();
	}

	public function addWin(Player $player): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("wins", $config->get("wins") +1);
        $config->save();
	}

	public function addDefeat(Player $player): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("defeats", $config->get("defeats") +1);
        $config->save();
	}

    public function addElo(Player $player, int $elo): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("elo", $config->get("elo") +$elo);
        $config->save();
    }

    public function removeElo(Player $player, int $elo): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("elo", $config->get("elo") -$elo);
        $config->save();
    }

	public function setSlots(Player $player, int $stick, int $pickaxe, int $blocks): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("stick", $stick);
        $config->set("pickaxe", $pickaxe);
        $config->set("blocks", $blocks);
        $config->save();

        Main::getPlayers()[$player->getName()]->setStick($stick);
        Main::getPlayers()[$player->getName()]->setBlocks($blocks);
        Main::getPlayers()[$player->getName()]->setPickaxe($pickaxe);
	}

	public function getSlots(Player $player): ?array{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $stick = $config->get("stick");
        $pickaxe = $config->get("pickaxe");
        $blocks = $config->get("blocks");

        return ["stick" => $stick, "pickaxe" => $pickaxe, "blocks" => $blocks, "sticktype" => $config->get("sticktype") ?? ItemTypeIds::STICK];
	}

	public function getStats(string $name): array{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$name . ".yml", Config::YAML);
        $rounds = $config->get("rounds");
        $kills = $config->get("kills");
        $deaths = $config->get("deaths");
        $wins = $config->get("wins");
        $defeats = $config->get("defeats");
        $beds = $config->get("beds");

		return [
            "rounds" => $rounds,
            "kills" => $kills,
            "deaths" => $deaths,
            "wins" => $wins,
            "defeats" => $defeats,
            "beds" => $beds
        ];
	}

	public function setStick(Player $player, int $id): void{
        $config = new Config(Main::getInstance()->getDataFolder() . "players/" .$player->getName() . ".yml", Config::YAML);
        $config->set("sticktype", $id);
        Main::getPlayers()[$player->getName()]->setStick($id);
	}
}