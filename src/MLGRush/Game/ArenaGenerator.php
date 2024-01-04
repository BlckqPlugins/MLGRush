<?php

namespace MLGRush\Game;
use MLGRush\Main;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class ArenaGenerator{
	/** @var string */
	private $path = "";
	/** @var string */
	private $toPath;

	const MAX_GENERATE = 32;
	const START_MAX_GENERATE = 2;

	/**
	 * ArenaGenerator constructor.
	 * @param string $path
	 * @param string $toPath
	 */
	public function __construct(string $path, string $toPath){
		$this->path = $path;
		$this->toPath = $toPath;
	}

	/**
	 * Function startMatch
	 * @param Player $firstPlayer
	 * @param Player $secondPlayer
	 * @return void
	 */
	public function startMatch(Player $firstPlayer, Player $secondPlayer): void{
		$mapName = GameManager::get()->getRandomArena();
		$count = $this->countWorlds($mapName);
		$generatedName = $this->copyMap($mapName, $count);
		$arenaData = (new Config(Main::getInstance()->getDataFolder() . "arenas/{$mapName}.yml", Config::YAML))->getAll();
		$arenaData["levelName"] = "{$mapName}-{$count}";
		$arenaData["mapName"] = $mapName;

		Server::getInstance()->getWorldManager()->loadWorld($arenaData["levelName"], true);

		$level = GameManager::get()->prepareLevel($arenaData["levelName"]);
		$game = new Game($firstPlayer, $secondPlayer, $arenaData, $level, GameManager::get());
		GameManager::get()->games[] = $game;
		$game->startGame();
	}

	/**
	 * Function countWorlds
	 * @param string $worldName
	 * @return int
	 */
	public function countWorlds(string $worldName): int{
		$arena = $worldName;
		$worldList = [];

		foreach (array_diff(scandir($this->toPath), ["..","."]) as $world) {
			$ex = explode("-", $world);
			if ($ex[0] == $worldName) {
				$worldList[] = explode("-", $world)[1];
			}
		}
		$i = 1;
		while (in_array($i, $worldList)) {
			$i++;
		}
		return $i;
	}

    /**
     * Funktion copyMap
     * @param string $worldName
     * @param int $count
     * @return string
     */
    public function copyMap(string $worldName, int $count): string{
        $sourcePath = $this->path . "/" . $worldName;
        $destinationPath = $this->toPath . "/$worldName-$count";
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }
        $this->recursiveCopy($sourcePath, $destinationPath);

        return "$worldName-$count";
    }

    /**
     * Funktion recursiveCopy
     * @param string $source
     * @param string $dest
     */
    private function recursiveCopy(string $source, string $dest): void {
        $dir = opendir($source);
        @mkdir($dest);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($source . '/' . $file)) {
                    $this->recursiveCopy($source . '/' . $file, $dest . '/' . $file);
                } else {
                    copy($source . '/' . $file, $dest . '/' . $file);
                }
            }
        }

        closedir($dir);
    }


    /**
	 * Function createMapTemplate
	 * @param string $worldName
	 * @return void
	 */
	public function createMapTemplate(string $worldName): void{
		popen("cp -r {$this->toPath}/{$worldName} {$this->path}/{$worldName}", "r");
		popen("rm -r {$this->toPath}/{$worldName}", "r");
		Server::getInstance()->getLogger()->debug("§aCreated World-Backup: §2{$worldName}");
	}

	/**
	 * Function existBackupWorld
	 * @param string $worldName
	 * @return bool
	 */
	public function existBackupWorld(string $worldName): bool{
		$exist = 0;
		foreach (glob($this->path . "/$worldName") as $value) {
			$exist++;
		}
		return $exist > 0;
	}

	/**
	 * Function removeMap
	 * @param string $worldName
	 * @return void
	 */
	public function removeMap(string $worldName): void{
		Server::getInstance()->getWorldManager()->unloadWorld(Server::getInstance()->getWorldManager()->getWorldByName($worldName));
		popen("rm -r " . $this->toPath . $worldName, "r");
		Server::getInstance()->getLogger()->debug("§4{$worldName} was deleted.");
	}
}
