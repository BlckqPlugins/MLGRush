<?php
namespace MLGRush\Game;

use MLGRush\Database\StatsAPI;
use MLGRush\Game\Tasks\RemoveBlocksTask;
use MLGRush\Game\Tasks\UnfreezePlayerTask;
use MLGRush\Lobby\LobbyMain;
use MLGRush\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Durable;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\player\GameMode;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\sound\GhastShootSound;
use pocketmine\world\World;
use xxflorii\scoreboard\ScoreboardAPI;

class Game{
	public $manager;
	public $db;
	public $level;
	public $mapName;
	/** @var Player */
	public $redplayer;
	/** @var Position */
	public $redspawn;
	public $redpoints = 0;
	public $redinv = [];
	/** @var Player */
	public $blueplayer;
	/** @var Position */
	public $bluespawn;
	public $bluepoints = 0;
	public $blueinv = [];
	public $maxheight;
	public $minheight;
	public $kills = [];
	public $deaths = [];
	public $round = 0;
	public $blocks = [];
	/** @var int */
	public $started;
	public $uuid;


	public function __construct(Player $redplayer, Player $blueplayer, array $config, World $level, GameManager $manager){
		$this->uuid = uniqid();
		$this->redplayer = $redplayer;
		$this->blueplayer = $blueplayer;
		$redcfg = $config["spawnred"];
		$this->mapName = $config["mapName"];
		$this->redspawn = new Position($redcfg[0], $redcfg[1], $redcfg[2], $level);
		$bluecfg = $config["spawnblue"];
		$this->bluespawn = new Position($bluecfg[0], $bluecfg[1], $bluecfg[2], $level);
		$this->minheight = $config["minheighty"];
		$this->maxheight = $config["maxheighty"];
		$this->level = $level;
		$this->manager = $manager;
		$this->started = time();
		$this->db = StatsAPI::get();
		$redinv = $this->db->getSlots($redplayer);

        $redstick = null;
        foreach (VanillaItems::getAll() as $item){
            if ($item->getTypeId() == $redinv["sticktype"]){
                $redstick = $item;
            }
        }

        if (is_null($redstick)){
            StatsAPI::get()->setStick($redplayer, ItemTypeIds::STICK);
            $redstick = VanillaItems::STICK();
        }

        if ($redstick instanceof Durable){
            $redstick->setUnbreakable(true);
        }
		$this->redinv = [
            Main::getPlayers()[$redplayer->getName()]->getStick() - 1   => $redstick,
            Main::getPlayers()[$redplayer->getName()]->getPickaxe() - 1 => VanillaItems::STONE_PICKAXE()->setUnbreakable(true),
            Main::getPlayers()[$redplayer->getName()]->getBlocks() - 1  => VanillaBlocks::SANDSTONE()->asItem()->setCount(64)
		];
		$blueinv = $this->db->getSlots($blueplayer);

        $bluestick = null;
        foreach (VanillaItems::getAll() as $item){
            if ($item->getTypeId() == $blueinv["sticktype"]){
                $bluestick = $item;
            }
        }

        if (is_null($bluestick)){
            StatsAPI::get()->setStick($blueplayer, ItemTypeIds::STICK);
            $bluestick = VanillaItems::STICK();
        }

        if ($bluestick instanceof Durable){
            $bluestick->setUnbreakable(true);
        }
		$this->blueinv = [
            Main::getPlayers()[$blueplayer->getName()]->getStick() - 1   => $redstick,
            Main::getPlayers()[$blueplayer->getName()]->getPickaxe() - 1 => VanillaItems::STONE_PICKAXE()->setUnbreakable(true),
            Main::getPlayers()[$blueplayer->getName()]->getBlocks() - 1  => VanillaBlocks::SANDSTONE()->asItem()->setCount(64)
		];
	}

	public function startGame(): void{

		if ((new LobbyMain(Main::getInstance()))->isInQueue($this->redplayer)) {
			(new LobbyMain(Main::getInstance()))->removeFromQueue($this->redplayer);
		}

		if ((new LobbyMain(Main::getInstance()))->isInQueue($this->blueplayer)) {
			(new LobbyMain(Main::getInstance()))->removeFromQueue($this->blueplayer);
		}

		$this->kills[$this->redplayer->getName()] = 0;
		$this->deaths[$this->redplayer->getName()] = 0;
        $this->redplayer->sendMessage("§aYour enemy is §e{$this->blueplayer->getDisplayName()}§7.");
		$this->kills[$this->blueplayer->getName()] = 0;
		$this->deaths[$this->blueplayer->getName()] = 0;
        $this->blueplayer->sendMessage("§aYour enemy is §e{$this->redplayer->getDisplayName()}§7.");
		$this->newRound();
        $scoreboard = new ScoreboardAPI();
        $scoreboard->removeLine($this->redplayer, 2);
        $scoreboard->removeLine($this->redplayer, 3);
        $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
        $scoreboard->setLine($this->redplayer, 1, "   ");
        $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
        $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");

        $scoreboard->removeLine($this->blueplayer, 2);
        $scoreboard->removeLine($this->blueplayer, 3);
        $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
        $scoreboard->setLine($this->blueplayer, 1, "   ");
        $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
        $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
	}

	public function newRound(): void{
		$this->resetLevel();
        $this->respawnPlayer($this->redplayer);
        $this->respawnPlayer($this->blueplayer);
        $this->giveItems($this->redplayer);
        $this->giveItems($this->blueplayer);
        $scoreboard = new ScoreboardAPI();
        $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
        $scoreboard->setLine($this->redplayer, 1, "   ");
        $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
        $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
        $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
        $scoreboard->setLine($this->blueplayer, 1, "   ");
        $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
        $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
		//$this->broadcastScoreboard();
		$this->round++;
	}


	public function playerFell(Player $player){
		$this->respawnPlayer($player);

        $scoreboard = new ScoreboardAPI();
		if ($player === $this->redplayer) {
            $scoreboard->removeLine($this->redplayer, 2);
            $scoreboard->removeLine($this->redplayer, 3);
            $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->redplayer, 1, "   ");
            $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");

            $scoreboard->removeLine($this->blueplayer, 2);
            $scoreboard->removeLine($this->blueplayer, 3);
            $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->blueplayer, 1, "   ");
            $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
			$this->kills[$this->blueplayer->getName()]++;
			$this->deaths[$this->redplayer->getName()]++;
			$this->sendKillSound($this->blueplayer);
			$this->sendDeathSound($this->redplayer);
		} else {
            $scoreboard->removeLine($this->redplayer, 2);
            $scoreboard->removeLine($this->redplayer, 3);
            $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->redplayer, 1, "   ");
            $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");

            $scoreboard->removeLine($this->blueplayer, 2);
            $scoreboard->removeLine($this->blueplayer, 3);
            $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->blueplayer, 1, "   ");
            $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
			$this->kills[$this->redplayer->getName()]++;
			$this->deaths[$this->blueplayer->getName()]++;
			$this->sendKillSound($this->redplayer);
			$this->sendDeathSound($this->blueplayer);
		}
		$this->giveItems($player);
	}

	/**
	 * Function getPlayerCount
	 * @param null|bool $spectators
	 * @return void
	 */
	public function getPlayerCount(?bool $spectators=false): int{
		$players = 0;
		foreach ($this->level->getPlayers() as $spectator) {
			if ($spectator instanceof Player) {
				if ($spectators) {
					if ($spectator->isSpectator()) {
						$players++;
					}
				} else {
					if (!$spectator->isSpectator()) {
						$players++;
					}
				}
			}
		}
		return $players;
	}

	public function giveItems(Player $player): void
    {
        if ($player !== null) {
            if (
                !is_null($player->getNetworkSession()) &&
                $player->isConnected() &&
                $player->getNetworkSession()->getInvManager() instanceof InventoryManager
            ) {
                $inv = $player->getInventory();
                $inv->clearAll();
                if ($player === $this->redplayer) {
                    foreach ($this->redinv as $slot => $item) {
                        $inv->setItem($slot, $item);
                    }
                } else {
                    foreach ($this->blueinv as $slot => $item) {
                        $inv->setItem($slot, $item);
                    }
                }
            }
        }
    }

	public function brokeBed(Player $player): void{
		if ($player === $this->redplayer) {
			$this->redpoints++;

            $this->redplayer->sendMessage(Main::getInstance()->prefix . "§cYou have broken the bed of your enemy§8.");
            $this->blueplayer->sendMessage(Main::getInstance()->prefix . "§cYour enemy has destroyed your bed§8.");

        } else {
			$this->bluepoints++;

            $this->blueplayer->sendMessage(Main::getInstance()->prefix . "§cYou have broken the bed of your enemy§8.");
            $this->redplayer->sendMessage(Main::getInstance()->prefix . "§cYour enemy has destroyed your bed§8.");

        }
        $scoreboard = new ScoreboardAPI();
        $scoreboard->removeLine($this->redplayer, 2);
        $scoreboard->removeLine($this->redplayer, 3);
        $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
        $scoreboard->setLine($this->redplayer, 1, "   ");
        $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
        $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
        $scoreboard->removeLine($this->blueplayer, 2);
        $scoreboard->removeLine($this->blueplayer, 3);
        $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
        $scoreboard->setLine($this->blueplayer, 1, "   ");
        $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
        $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");

        if ($this->round < $this->manager->lengthrounds) {
			$this->newRound();
		} else {
			if ($this->redpoints == $this->bluepoints) {
				$this->broadcastMessage("§cGame ended.");
			} else {
				$this->endGame($this->getWinner());
			}
			$this->broadcastBedSound();
		}
	}

	public function resetLevel(): void{
		if (count($this->blocks) > 0) {
			$this->manager->plugin->getScheduler()->scheduleRepeatingTask(new RemoveBlocksTask($this->level, $this->blocks), 2);
		}
		$this->blocks = [];
	}

	public function endGame(Player $winner){
		if ($this->redplayer === $winner) {
			$this->sendWinSound($this->redplayer);

			$loser = $this->blueplayer;
		} else {
			$this->sendWinSound($this->blueplayer);

			$loser = $this->redplayer;
		}
		$this->db->addWin($winner);
		$this->db->addDefeat($loser);
		$this->db->addBeds($this->redplayer, $this->redpoints);
		$this->db->addBeds($this->blueplayer, $this->bluepoints);
		foreach ($this->kills as $name => $kills) {
			$this->db->addKills($name, $kills);
		}
		foreach ($this->deaths as $name => $deaths) {
			$this->db->addDeaths($name, $deaths);
		}

        $this->db->addRound([$this->redplayer, $this->blueplayer]);
		$this->broadcastMessage("§aThe player §e{$winner->getDisplayName()} §awon the game.");

        $lm = LobbyMain::get();

		foreach ($this->level->getPlayers() as $levelplayer){
		    $levelplayer->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		    $levelplayer->setGamemode(GameMode::SURVIVAL());
            $lm->giveItems($levelplayer, true);
        }

		$this->teleportAll($this->manager->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

		$lm->giveItems($this->redplayer, true);
		$lm->giveItems($this->blueplayer, true);
		GameManager::$arenaGenerator->removeMap($this->level->getFolderName());

		$k = array_search($this, $this->manager->games);
		unset($this->manager->games[$k]);
	}

	public function getWinner(): Player{
		if ($this->redpoints < $this->bluepoints) {
			return $this->blueplayer;
		} else {
			return $this->redplayer;
		}
	}

	public function broadcastMessage(string $msg): void{
		$this->blueplayer->sendMessage($msg);
		$this->redplayer->sendMessage($msg);
	}

	public function broadcastPopup(string $msg): void{
		$this->blueplayer->sendPopup($msg);
		$this->redplayer->sendPopup($msg);
	}

	public function sendDeathSound(Player $player): void{
	}

	public function sendKillSound(Player $player): void{
	}

	public function sendWinSound(Player $player): void{
	}

    public function broadcastBedSound(): void{
        $this->level->addSound(new Vector3($this->redplayer->getPosition()->x, $this->redplayer->getPosition()->y, $this->redplayer->getPosition()->z), new GhastShootSound());
    }

	public function respawnPlayer(Player $player): void{
        $scoreboard = new ScoreboardAPI();
		if ($player === $this->redplayer) {
            $scoreboard->removeLine($this->redplayer, 2);
            $scoreboard->removeLine($this->redplayer, 3);
            $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->redplayer, 1, "   ");
            $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");

            $scoreboard->removeLine($this->blueplayer, 2);
            $scoreboard->removeLine($this->blueplayer, 3);
            $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->blueplayer, 1, "   ");
            $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
			$x = $this->bluespawn->x - $this->redspawn->x;
			$y = $this->bluespawn->y - $this->redspawn->y;
			$z = $this->bluespawn->z - $this->redspawn->z;
			$yaw = rad2deg(atan2(-$x, $z));
			$pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
			$player->teleport($this->redspawn, $yaw, $pitch);
		} else {
            $scoreboard->removeLine($this->redplayer, 2);
            $scoreboard->removeLine($this->redplayer, 3);
            $scoreboard->create($this->redplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->redplayer, 1, "   ");
            $scoreboard->setLine($this->redplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->redplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");

            $scoreboard->removeLine($this->blueplayer, 2);
            $scoreboard->removeLine($this->blueplayer, 3);
            $scoreboard->create($this->blueplayer, "   §bMLG§fRush   ");
            $scoreboard->setLine($this->blueplayer, 1, "   ");
            $scoreboard->setLine($this->blueplayer, 2, "  §8» §4" . substr($this->redplayer->getDisplayName(), 0, 8) . "§f: §c" . $this->redpoints . "  ");
            $scoreboard->setLine($this->blueplayer, 3, "  §8» §1" . substr($this->blueplayer->getDisplayName(), 0, 8) . "§f: §9" . $this->bluepoints . "   ");
			$x = $this->redspawn->x - $this->bluespawn->x;
			$y = $this->redspawn->y - $this->bluespawn->y;
			$z = $this->redspawn->z - $this->bluespawn->z;
			$yaw = rad2deg(atan2(-$x, $z));
			$pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
			$player->teleport($this->bluespawn, $yaw, $pitch);
		}
		$player->setNoClientPredictions();
		$this->manager->plugin->getScheduler()->scheduleDelayedTask(new UnfreezePlayerTask($player), 5);
	}

	public function teleportAll(Vector3 $target): void{
		//$this->redplayer->removeScoreboard();
		$this->redplayer->teleport($target);
		$this->blueplayer->teleport($target);
		//$this->blueplayer->removeScoreboard();
		$this->redplayer->setLastDamageCause(new EntityDamageEvent($this->redplayer, EntityDamageEvent::CAUSE_VOID, 0));
		$this->blueplayer->setLastDamageCause(new EntityDamageEvent($this->blueplayer, EntityDamageEvent::CAUSE_VOID, 0));
        $scoreboard = new ScoreboardAPI();
        $scoreboard->remove($this->redplayer);
        $scoreboard->remove($this->blueplayer);
	}
}