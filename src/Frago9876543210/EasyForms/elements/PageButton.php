<?php
namespace Frago9876543210\EasyForms\elements;
use Closure;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

class PageButton extends Button{
	/** @var null|Closure */
	protected $onClick = null;


	/**
	 * PageButton constructor.
	 * @param string $text
	 * @param null|Closure $onClick
	 * @param null|Image $image
	 */
	public function __construct(string $text, ?Closure $onClick = null, ?Image $image = null){
		Utils::validateCallableSignature(function (Player $player): void{}, $onClick);
		$this->onClick = $onClick;
		parent::__construct($text, $image);
	}

	/**
	 * Function onClick
	 * @param Player $player
	 * @return void
	 */
	public function onClick(Player $player): void{
		($this->onClick)($player);
	}
}
