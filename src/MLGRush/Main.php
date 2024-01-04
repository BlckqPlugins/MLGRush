<?php
namespace MLGRush;

use MLGRush\Commands\LeaveCommand;
use MLGRush\Commands\MLGCommand;
use MLGRush\Commands\QueueCommand;
use MLGRush\Commands\SortCommand;
use MLGRush\Database\StatsAPI;
use MLGRush\Game\ArenaGenerator;
use MLGRush\Game\GameManager;
use MLGRush\Lobby\LobbyMain;
use MLGRush\player\PlayerSession;
use pocketmine\item\ItemTypeIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{
    public $prefix;

    public $lobby;
    public $gamemanager;

    /** @var PlayerSession[] */
    public static array $players = [];

    public $items = ["Stick", "Blaze Rod", "Enderpearl", "Mushroom", "Apple", "Fish", "Egg", "Sword", "Paper", "Emerald"];
    public $icons = ["textures/items/stick", "textures/items/blaze_rod", "textures/items/ender_pearl", "textures/blocks/mushroom_red", "textures/items/apple", "textures/items/fish_cooked", "textures/items/egg", "textures/items/gold_sword", "textures/items/paper", "textures/items/emerald"];
    public $ids   = [ItemTypeIds::STICK, ItemTypeIds::BLAZE_ROD, ItemTypeIds::ENDER_PEARL, ItemTypeIds::MUSHROOM_STEW, ItemTypeIds::APPLE, ItemTypeIds::COOKED_FISH, ItemTypeIds::EGG, ItemTypeIds::GOLDEN_SWORD, ItemTypeIds::PAPER, ItemTypeIds::EMERALD];

    public array $hitDelay;
    protected static $database;

    private static $instance;

    public function onEnable(): void {

        $this->hitDelay = [];

    	self::$instance = $this;
        @mkdir($this->getDataFolder() . "/arenas/");
        @mkdir($this->getDataFolder() . "/players/");
        GameManager::$arenaGenerator = new ArenaGenerator("{$this->getDataFolder()}backups/", "{$this->getServer()->getDataPath()}worlds/");

        $this->prefix = "§bMLG§fRush§7 »§r ";

        $this->registerCommands();

        self::$database = new StatsAPI();
        self::$database->load();

        $this->lobby = new LobbyMain($this);
        $this->gamemanager = new GameManager($this);

        $operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        if ($operator !== null) {
            foreach ($this->items as $item) {
                DefaultPermissions::registerPermission(new Permission("mlgrush.stick.{$item}"), [$operator]);
            }
            DefaultPermissions::registerPermission(new Permission("mlgrush.stick.all"), [$operator]);
        }
    }

    public static function getInstance(): self {
    	return self::$instance;
	}

    /**
     * @return mixed
     */
    public static function getDatabase(): StatsAPI
    {
        return self::$database;
    }
	
	public function _getDataFolder_(): string
    {
		return $this->getDataFolder();
	}

    public function registerCommands(): void {
        $cmdmap = $this->getServer()->getCommandMap();

        $cmdmap->registerAll("mlgrush", [
            new MLGCommand(),
            new LeaveCommand(),
            new QueueCommand(),
            new SortCommand(),
        ]);
    }

    /**
     * @return PlayerSession[]
     */
    public static function getPlayers(): array
    {
        return self::$players;
    }
}