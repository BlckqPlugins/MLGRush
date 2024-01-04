<?php
namespace MLGRush\Game;
use MLGRush\Game\Tasks\CheckPlayersTask;
use MLGRush\Game\Tasks\GamePopupTask;
use MLGRush\Lobby\LobbyMain;
use MLGRush\Main;
use pocketmine\player\GameMode;
use pocketmine\world\World;
use pocketmine\player\Player;
use pocketmine\Server;

class GameManager{
	public Main $plugin;
	public static GameManager $self;
	/** @var ArenaGenerator */
	public static ArenaGenerator $arenaGenerator;

	public static function get(): GameManager{
		return self::$self;
	}

	public $games = [];
	public $lengthrounds;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		$this->lengthrounds = 10;

		$plugin->getServer()->getPluginManager()->registerEvents(new GameListener($this), $plugin);
		$plugin->getScheduler()->scheduleRepeatingTask(new GamePopupTask(), 20);
		$plugin->getScheduler()->scheduleRepeatingTask(new CheckPlayersTask($this), 2);
		self::$self = $this;
	}

	/**
	 * Function getAvailableArena
	 * @return string|bool
     * @deprecated GameManager::getRandomArena
	 */
	public function getAvailableArena(): string|bool
    {
		$arenas = array_diff(scandir($this->plugin->_getDataFolder_() . "/arenas/"), ['..', '.']);
		foreach ($arenas as $arena) {
            $parts = explode(".", $arena);
			foreach ($this->games as $game) {
				if ($game->level->getFolderName() == $parts[0]) {
                    if (empty($arenas)) return false;
					$k = array_search($arena, $arenas);
					unset($arenas[$k]);
				}
			}
		}
		$arenas = array_values($arenas);
		if (isset($arenas[0])) {
			return explode(".", $arenas[mt_rand(0, (count($arenas)) - 1)])[0];
		} else {
			return false;
		}
	}

	/**
	 * Function getRandomArena
	 * @return bool
     */
	public function getRandomArena(): bool
    {
		$arenas = array_diff(scandir($this->plugin->_getDataFolder_() . "/arenas/"), ['..', '.']);
		$arenas = array_values($arenas);
		if (isset($arenas[0])) {
			return explode(".", $arenas[mt_rand(0, (count($arenas)) - 1)])[0];
		} else {
			return false;
		}
	}

	/**
	 * Function leave
	 * @param Player $player
	 * @return void
	 */
	public function leave(Player $player): void
    {
		if ($this->isInGame($player)) {
			$game = $this->getGameByPlayer($player);
			$game->broadcastMessage("§cYour enemy §e{$player->getDisplayName()} §cleft the game.");
			if ($game->redplayer === $player) {
				$winner = $game->blueplayer;
			} else {
				$winner = $game->redplayer;
			}
			$game->endGame($winner);
		} else {
			if ($player->isSpectator()) {
				$player->setGamemode(GameMode::SURVIVAL());
				$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
				$lm = LobbyMain::get();
                $lm->giveItems($player, true);
			}
		}
		if ($player->getWorld()->getFolderName() !== Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName()) {
            $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            $player->setGamemode(GameMode::SURVIVAL());
            $lm = LobbyMain::get();
            $lm->giveItems($player, true);
        }
	}

    public function prepareLevel(string $arena): ?World {
        $worldManager = $this->plugin->getServer()->getWorldManager();

        if (!$worldManager->isWorldGenerated($arena)) {
            // Handle the case where the world doesn't exist or failed to load.
            $this->plugin->getLogger()->error("World '$arena' does not exist or failed to load.");
            return null;
        }

        $world = $worldManager->getWorldByName($arena);

        if ($world === null) {
            // Handle the case where the world exists but couldn't be retrieved.
            $this->plugin->getLogger()->error("World '$arena' exists but could not be retrieved.");
            return null;
        }

        return $world;
    }


    public function isInGame(Player $player): bool{
		foreach ($this->games as $game) {
			if ($game->redplayer === $player or $game->blueplayer === $player) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Function getGameByPlayer
	 * @param Player $player
	 * @return null|Game
	 */
	public function getGameByPlayer(Player $player): ?Game{
		foreach ($this->games as $game) {
			if ($game->redplayer === $player or $game->blueplayer === $player) {
				return $game;
			}
		}
		return null;
	}
}