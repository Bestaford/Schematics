<?php

namespace Bestaford;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;

class Schematics extends PluginBase {
	
	public $pos1;
	public $pos2;

	public function onEnable() {
		@mkdir($this->getDataFolder());
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if(!$sender instanceof Player) {
			$this->sendMessage($sender, "Команда доступна только в игре");
			return true;
		}
		if(count($args) == 0) {
			return $this->usage($sender);
		}
		$time = microtime(true);
		$level = $sender->getLevel();
		switch($args[0]) {
			case "pos1":
			$this->pos1 = new Vector3($sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ());
			if(($this->pos1 !== null) && ($this->pos2 !== null)) {
				$count = $this->getBlocksCount($sender);
				$this->sendMessage($sender, "Позиция 1 установлена на §a{$this->pos1->getX()}§f, §a{$this->pos1->getY()}§f, §a{$this->pos1->getZ()}§r. Выделено блоков: §a{$count}");
				if($count > 10000) {
					$this->lagWarning($sender);
				}
			} else {
				$this->sendMessage($sender, "Позиция 1 установлена на §a{$this->pos1->getX()}§f, §a{$this->pos1->getY()}§f, §a{$this->pos1->getZ()}");
			}
			break;
			case "pos2":
			$this->pos2 = new Vector3($sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ());
			if(($this->pos1 !== null) && ($this->pos2 !== null)) {
				$count = $this->getBlocksCount($sender);
				$this->sendMessage($sender, "Позиция 2 установлена на §a{$this->pos2->getX()}§f, §a{$this->pos2->getY()}§f, §a{$this->pos2->getZ()}§r. Выделено блоков: §a{$count}");
				if($count > 10000) {
					$this->lagWarning($sender);
				}
			} else {
				$this->sendMessage($sender, "Позиция 2 установлена на §a{$this->pos2->getX()}§f, §a{$this->pos2->getY()}§f, §a{$this->pos2->getZ()}");
			}
			break;
			case "list":
			$files = scandir($this->getDataFolder());
			array_shift($files);
			array_shift($files);
			$text = "";
			foreach($files as $file) {
			$file = explode(".", $file);
			if(end($file) == "schematic") {
				$text .= "\n§a{$file[0]}";
				}	
			}
			$text = trim($text);
			if($text == "") {
				$this->sendMessage($sender, "Список схем пуст");
			} else {
				$this->sendMessage($sender, "Список схем:\n{$text}", false);
			}
			break;
			case "export":
			if(count($args) < 2) {
				return $this->usage($sender);
			}
			if(!(($this->pos1 !== null) && ($this->pos2 !== null))) {
				$this->sendMessage($sender, "Сначала установите позиции командами §a/schem pos1 §rи §a/schem pos2");
				return true;
			}
			$name = $args[1];
			$blocks = [];
			$ignored = [Block::AIR, Block::GRASS, Block::DIRT, Block::CHEST, Block::FURNACE];
			$pos1 = $this->pos1;
			$pos2 = $this->pos2;
			$pos = new Vector3(min($pos1->x, $pos2->x), min($pos1->y, $pos2->y), min($pos1->z, $pos2->z));
			$this->sendMessage($sender, "Начался экспорт выделенной области в схему §a{$name}");
			for ($x = $pos->x; $x <= max($pos1->x, $pos2->x); $x++) {
				for ($y = $pos->y; $y <= max($pos1->y, $pos2->y); $y++) {
					for ($z = $pos->z; $z <= max($pos1->z, $pos2->z); $z++) {
						$id = $level->getBlockIdAt($x, $y, $z);
						if(in_array($id, $ignored)) {
							continue;
						}
						$blocks[] = ["x" => $x, "y" => $y, "z" => $z, "id" => $id, "meta" => $level->getBlockDataAt($x, $y, $z)];
					}
				}
			}
			$path = $this->getPath($name);
			file_put_contents($path, serialize($blocks));
			$count = count($blocks);
			$time = round((microtime(true) - $time), 2);
			$size = round(filesize($path) / 1000);
			$this->sendMessage($sender, "Экспорт схемы §a{$name} §rзавершён. Блоков: §a{$count}§r. Время: §a{$time} §rсекунд§r. Размер: §a{$size} §rкилобайт");
			break;
			case "import":
				if(count($args) < 2) {
					return $this->usage($sender);
				}
				$name = $args[1];
				$path = $this->getPath($name);
				if(!is_file($path)) {
					$this->sendMessage($sender, "Схемы с именем §a{$name} §rне существует");
					return true;
				}
				$blocks = unserialize(file_get_contents($path));
				$count = count($blocks);
				$size = round(filesize($path) / 1000);
				$this->sendMessage($sender, "Начался импорт схемы §a{$name}§r. Блоков: §a{$count}§r. Размер: §a{$size} §rкилобайт");
				foreach($blocks as $block) {
					$x = $block["x"];
					$y = $block["y"];
					$z = $block["z"];
					if(!$level->isChunkGenerated($x >> 4, $z >> 4)) $level->generateChunk($x >> 4, $z >> 4);
					if(!$level->isChunkLoaded($x >> 4, $z >> 4)) $level->loadChunk($x >> 4, $z >> 4);
					if($level->setBlock(new Vector3($x, $y, $z), Block::get($block["id"], $block["meta"]), false, false)) {
						continue;
					} else {
						$this->sendMessage($sender, "Произошла ошибка во время импорта схемы §a{$name}§r. Отмена операции");
						return true;
					}
				}
				$time = round((microtime(true) - $time), 2);
				$this->sendMessage($sender, "Схема §a{$name} §rуспешно импортирована. Блоков: §a{$count}§r. Время: §a{$time} §rсекунд");
				break;
				case "remove":
				if(count($args) < 2) {
					return $this->usage($sender);
				}
				$name = $args[1];
				$path = $this->getPath($name);
				if(!is_file($path)) {
					$this->sendMessage($sender, "Схемы с именем §a{$name} §rне существует");
					return true;
				}
				unlink($path);
				$this->sendMessage($sender, "Схема §a{$name} §rуспешно удалена");
				break;
				default:
				return $this->usage($sender);
			}
			return true;
		}
		
		public function sendMessage($player, $message, $dot = true) {
			$message = "§r§a[Schematics] §r".$message."§r";
			if($dot) {
				$message .= ".";
			}
			$player->sendMessage($message);		
		}
		
		public function getPath($name) {
			return $this->getDataFolder()."{$name}.schematic";
		}
		
		public function usage($player) {
			$version = "1.0";
			$author = "§dBestaford";
			$this->sendMessage($player, "{$version} by {$author}§r\n§a/schem pos1 §7- §fустановить позицию 1\n§a/schem pos2 §7- §fустановить позицию 2\n§a/schem list §7 - §fпоказать список схем\n§a/schem export <name> §7- §fэкспортировать блоки в схему\n§a/schem import <name> §7- §fимпортировать блоки из схемы\n§a/schem remove <name> §7- §fудалить схему", false);
			return true;
		}
		
		public function getBlocksCount($player) {
			$level = $player->getLevel();
			$blocks = 0;
			$ignored = [Block::AIR, Block::GRASS, Block::DIRT];
			$pos1 = $this->pos1;
			$pos2 = $this->pos2;
			$pos = new Vector3(min($pos1->x, $pos2->x), min($pos1->y, $pos2->y), min($pos1->z, $pos2->z));
			for ($x = $pos->x; $x <= max($pos1->x, $pos2->x); $x++) {
				for ($y = $pos->y; $y <= max($pos1->y, $pos2->y); $y++) {
					for ($z = $pos->z; $z <= max($pos1->z, $pos2->z); $z++) {
						$id = $level->getBlockIdAt($x, $y, $z);
						if(in_array($id, $ignored)) {
							continue;
						}
						$blocks++;
					}
				}
			}
			return $blocks;
		}
		
		public function lagWarning($player) {
			$this->sendMessage($player, "Внимание: экспорт и/или импорт такого количества блоков может вызвать лаги на сервере");
		}
}