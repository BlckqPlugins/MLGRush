<?php
namespace MLGRush\Lobby;

use bedrockcloud\cloudbridge\network\packet\SendToHubPacket;
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\elements\Slider;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use Frago9876543210\EasyForms\forms\MenuForm;
use MLGRush\Database\StatsAPI;
use MLGRush\Game\Game;
use MLGRush\Game\GameManager;
use MLGRush\Lobby\Tasks\ResetCooldownTask;
use MLGRush\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\EnderPearl;
use pocketmine\item\ProjectileItem;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class LobbyListener implements Listener{
	public $lobby;

	public function __construct(LobbyMain $lobby){
		$this->lobby = $lobby;
	}

    public function buyForm(Player $player, string $stick, string $permission){
    }

    public function sendChoose(Player $player) {
    }

    public function sendChooseForm(Player $player, array $players): void{
        foreach ($players as $name) {
            $buttons[] = new Button($name);
        }
        $player->sendForm(new MenuForm("§2Stats", "", $buttons, function (Player $player, Button $selected): void{
            $this->showStats($player, TextFormat::clean($selected->getText()));
        }));
    }

    public function showStats(Player $player, string $name): void{
    }

    /**
     * Function arenaPage
     * @param Player $player
     * @param int|null $pageNumber
     * @return void
     */
    private function arenaPage(Player $player, ?int $pageNumber=1): void{
        $pageHeight = 3;
        $arenas = [];

        /** @var Game $runningGame */
        foreach (GameManager::get()->games as $runningGame) {
            $arenas[$runningGame->uuid] = $runningGame;
        }
        shuffle($arenas);
        $arenas = array_chunk($arenas, $pageHeight);
        $pageNumber = min(count($arenas), $pageNumber);
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }
        $_arenas = $buttons = [];
        $title = "Spectate" . " - {$pageNumber}";
        if (isset($arenas[$pageNumber -1])) {
            /** @var Game $runningArena */
            foreach ($arenas[$pageNumber -1] as $runningArena) {
                $key = "§e{$runningArena->mapName}§r\n§c{$runningArena->getPlayerCount(false)} " . "Players" . " §8| §c{$runningArena->getPlayerCount(true)} " . " Spectators" . "§r";
                $buttons[] = $key;
                $_arenas[$key] = $runningArena;
            }
        }
        $buttons[] = (isset($arr[$pageNumber -2]) ? "<-" : "§cBack");
        if (isset($arr[$pageNumber])) {
            $buttons[] = "->";
        }

        $player->sendForm(new MenuForm(
            $title,
            "",
            $buttons,
            function (Player $player, Button $button) use ($pageNumber, $_arenas): void{
                $translator = [
                    "->" => 1,
                    "<-" => -1,
                ];
                if (in_array($button->getText(), ["<-","->"])) {
                    $newPage = $pageNumber + $translator[$button->getText()];
                    $this->arenaPage($player, $newPage);
                    return;
                } else if ($button->getText() == "§cBack") {
                    return;
                } else {
                    /** @var Game $selectedArena */
                    $selectedArena = $_arenas[$button->getText()];

                    if (is_null($selectedArena->level) or !$selectedArena->level->isLoaded()) {
                        $player->sendMessage("§cThis arena don't exists!");
                        return;
                    }

                    try {
                        if ($selectedArena->level instanceof World) {
                            $player->setGamemode(GameMode::SPECTATOR());
                            $player->teleport($selectedArena->redspawn);
                            $player->getArmorInventory()->clearAll();
                            $player->getInventory()->clearAll();
                            $player->getCursorInventory()->clearAll();
                        }
                    } catch (AssumptionFailedError $exception){
                        $player->sendMessage("§cThis arena don't exists!");
                        return;
                    }
                }
            },
            function (Player $player): void{}
        ));
    }

    public function onLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        $db = StatsAPI::get();
        $db->initializePlayer($player);
    }

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$db = StatsAPI::get();

        $slots = $db->getSlots($player);
        Main::getPlayers()[$player->getName()]->setStick($slots["stick"]);
        Main::getPlayers()[$player->getName()]->setBlocks($slots["blocks"]);
        Main::getPlayers()[$player->getName()]->setPickaxe($slots["pickaxe"]);

		$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

		$event->setJoinMessage("");
		$player->setGamemode(GameMode::SURVIVAL());
		$this->lobby->giveItems($player);

        if (isset(Main::getInstance()->hitDelay[$player->getName()])) unset(Main::getInstance()->hitDelay[$player->getName()]);
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if ($this->lobby->isInQueue($player)) {
			$this->lobby->removeFromQueue($player);
		}
	}

	public function onDrop(PlayerDropItemEvent $event){
		$event->cancel();
	}

    public function onItemUse(PlayerItemUseEvent $event)
    {
        $item = $event->getItem();
        $player = $event->getPlayer();

        if ($item->getTypeId() === VanillaBlocks::GLASS_PANE()->getTypeId()) {
            return;
        }

        if (!in_array($player->getName(), Main::getInstance()->hitDelay)) {
            Main::getInstance()->hitDelay[] = $player->getName();
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ResetCooldownTask($player->getName()), 1);
            if ($item->getCustomName() === "§aQueue") {
                if (!$this->lobby->isInQueue($player)) {
                    $this->lobby->addToQueue($player);
                    $player->sendMessage(Main::getInstance()->prefix . "§aYou have joined the queue.");
                } else {
                    if ($this->lobby->isInQueue($player)) {
                        $this->lobby->removeFromQueue($player);
                        $player->sendMessage(Main::getInstance()->prefix . "§cYou have left the queue.");
                    }
                }
            } elseif ($item->getCustomName() === "§6CHANGE STICK") {
                $this->sendChoose($player);
            } elseif ($item->getCustomName() === "§bSORT INVENTORY") {
                $db = StatsAPI::get();
                $slots = $db->getSlots($player);
                $player->sendForm(new CustomForm(
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
            } elseif ($item->getCustomName() === "§dStats") {
                //ToDo: make stats form
            } elseif ($item->getCustomName() === "§eSpectate") {
                $this->arenaPage($player);
            } elseif ($item->getCustomName() === "§4HUB") {
            }
        }
    }

    public function onTransaction(InventoryTransactionEvent $event){
        $player = $event->getTransaction()->getSource();
        if ($this->lobby->isInLobby($player)) {
            $event->cancel();
        }
    }

    public function onCraft(CraftItemEvent $event){
        $event->cancel();
    }

    public function onDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            if ($this->lobby->isInLobby($entity)) {
                $event->cancel();

                if ($event->getCause() == EntityDamageEvent::CAUSE_VOID) {
                    $entity->teleport($entity->getWorld()->getSafeSpawn());
                }

                if ($event instanceof EntityDamageByEntityEvent) {
                    $attacker = $event->getDamager();
                    if ($attacker instanceof Player) {
                        if ($attacker->getInventory()->getItemInHand()->getTypeId() === VanillaItems::IRON_SWORD()->getTypeId()) {
                            if ($this->lobby->isInQueue($attacker)) {
                                $this->lobby->removeFromQueue($attacker);
                            }

                            Main::getPlayers()[$entity->getName()]->challenge($attacker);

                            if (!is_null(Main::getPlayers()[$entity->getName()]->getChallenger()) && !is_null(Main::getPlayers()[$attacker->getName()]->getChallenger()) && Main::getPlayers()[$entity->getName()]->getChallenger()->getName() == $attacker->getName() && Main::getPlayers()[$attacker->getName()]->getChallenger()->getName() == $entity->getName()) {
                                GameManager::$arenaGenerator->startMatch($entity, $attacker);
                                Main::getPlayers()[$entity->getName()]->challenge(null);
                                Main::getPlayers()[$attacker->getName()]->challenge(null);
                            }
                        }
                    }
                }
            }
        }
    }


    public function onMove(PlayerMoveEvent $event){
	    $player = $event->getPlayer();
	    $pos = $player->getPosition()->getY();

	    if ($player->getWorld()->getFolderName() === Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName()){
	        if ($pos <= 0) {
	            $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            }
        }
    }

	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if ($this->lobby->isInLobby($player)) {
			if (Server::getInstance()->isOp($player->getName()) && $player->isCreative()) {
                $event->uncancel();
			} else {
                $event->cancel();
            }
		}
	}

	public function onBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if ($this->lobby->isInLobby($player)) {
            if (Server::getInstance()->isOp($player->getName()) && $player->isCreative()) {
                $event->uncancel();
            } else {
                $event->cancel();
            }
		}
	}

    public function onUseItem(PlayerItemUseEvent $event){
        if ($event->getItem() instanceof Throwable || $event->getItem() instanceof ProjectileItem){
            $event->cancel();
        }
    }
}