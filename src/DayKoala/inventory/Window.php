<?php

/*
 *  __          ___           _                     
 *  \ \        / (_)         | |                    
 *   \ \  /\  / / _ _ __   __| | _____      ___   _ 
 *    \ \/  \/ / | | '_ \ / _` |/ _ \ \ /\ / / | | |
 *     \  /\  /  | | | | | (_| | (_) \ V  V /| |_| |
 *      \/  \/   |_|_| |_|\__,_|\___/ \_/\_/  \__, |
 *                                             __/ |
 *                                            |___/ 
 *  @author DayKoala
 *  @link https://github.com/DayKoala/Windowy
 * 
 */

namespace DayKoala\inventory;

use pocketmine\inventory\SimpleInventory;

use pocketmine\player\Player;

use pocketmine\world\Position;

use DayKoala\inventory\holder\PlayerHolder;

use DayKoala\inventory\action\WindowTransaction;

class Window extends SimpleInventory implements PlayerHolder{

    use WindowTrait;

    private $type;

    protected ?Player $holder;
    protected Position $position;

    protected string $name;

    protected ?\Closure $transaction = null;

    protected bool $closed = true;

    public function __construct(Int $type, Int $size, String $tile, Int $id){
        parent::__construct($size);

        $this->type = $type;

        $this->writeAdditionalIds($tile, $id);
    }

    public function getType() : Int{
        return $this->type;
    }

    public function setHolder(Player $player) : Void{
        $this->holder = $player;
    }

    public function getHolder() : ?Player{
        return $this->holder;
    }

    public function getPosition() : Position{
        return $this->position;
    }

    public function setName(String $name) : Void{
        $this->name = $name;
    }

    public function getName() : String{
        return $this->name;
    }

    public function hasTransaction() : Bool{
        return (Bool) $this->transaction;
    }

    public function setTransaction(\Closure $closure) : Void{
        $this->transaction = $closure;
    }

    public function getTransaction() : ?\Closure{
        return $this->transaction;
    }

    public function isClosed() : Bool{
        return $this->closed;
    }

    public function initContainer() : Bool{
        if($this->holder === null){
           return false;
        }
        $this->closed = false;

        $player = $this->holder;

        $pos = $player->getPosition()->floor();
        $pos = $this->position = new Position($pos->x, $pos->y + 3, $pos->z, $player->getWorld());

        $this->sendBlockPacket($player, $pos);

        $nbt = $this->newNBT($pos, $this->name);

        if($this->getSize() === 54):
            
           $this->sendBlockPacket($player, $side = $pos->add(1, 0, 0), $this->block);

           $compound = $this->newNBT($side, $this->name)

           ->setInt("pairx", $pos->x)
           ->setInt("pairz", $pos->z);

           $this->replace[] = $player->getWorld()->getBlock($side);

           $this->sendActorPacket($player, $side, $compound);

           $nbt

           ->setInt("pairx", $side->x)
           ->setInt("pairz", $side->z);

        endif;

        $this->replace[] = $player->getWorld()->getBlock($pos);

        $this->sendActorPacket($player, $pos, $nbt);

        return true;
    }

    public function onClose(Player $player) : Void{
        parent::onClose($player);

        foreach($this->replace as $block) $this->sendBlockPacket($player, $block->getPosition(), $block);

        $this->closed = true;
    }

}