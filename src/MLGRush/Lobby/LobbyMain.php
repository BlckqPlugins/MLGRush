<?php
namespace MLGRush\Lobby;

use MLGRush\Lobby\Tasks\QueueTask;
use MLGRush\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;


class LobbyMain{
	public $plugin;
	/** @var LobbyMain */
	public static $self;

	public static function get(): LobbyMain{
		return self::$self;
	}

	/** @var Player[] */
	public $queue = [];

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents(new LobbyListener($this), $plugin);
		$plugin->getScheduler()->scheduleRepeatingTask(new QueueTask(), 40);
		$this->prepareLevel();
		self::$self = $this;
	}

	public function prepareLevel(): void{
		$lvl = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
		$lvl->setAutoSave(false);
		$lvl->setTime(6000);
		$lvl->stopTime();
		$lvl->setDifficulty(0);
	}

	/** @return Player[] */
	public function getPlayersInLobby(): array{
		$arr = [];
		foreach ($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getPlayers() as $player) {
			$arr[] = $player->getName();
		}
		return $arr;
	}

	public function isInLobby(Player $player): bool{
		return in_array($player->getName(), $this->getPlayersInLobby());
	}

	public function queuePlayer(Player $player): void{
		if ($this->isInQueue($player)) {
			$this->removeFromQueue($player);
		} else {
			$this->addToQueue($player);
		}
	}

	public function isInQueue(Player $player): bool{
		return in_array($player->getName(), $this->queue);
	}

	public function addToQueue(Player $player): void{
        $player->sendTitle("§aWaiting for player...");
		$this->queue[] = $player->getName();
	}
	public function removeFromQueue(Player $player): void{

		$k = array_search($player->getName(), $this->queue);
		unset($this->queue[$k]);
		$this->queue = array_values($this->queue);
	}
	public function giveItems(Player $player, ?bool $clear=true): void{
		if (!$this->isInLobby($player)) {
			return;
		}

		if (
            !is_null($player->getNetworkSession()) &&
            $player->isConnected() &&
            $player->getNetworkSession()->getInvManager() instanceof InventoryManager
        ) {
            $inv = $player->getInventory();
            if ($clear) $inv->clearAll();
            $inv->setItem(4, VanillaItems::IRON_SWORD()->setCustomName("§cChallenge"));
            $inv->setItem(6, VanillaBlocks::CHEST()->asItem()->setCustomName("§bSORT INVENTORY"));
            $inv->setItem(2, VanillaItems::NETHER_STAR()->setCustomName("§eSpectate"));
        }
	}
}