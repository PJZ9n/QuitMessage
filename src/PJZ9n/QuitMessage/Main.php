<?php

/**
 * Copyright (c) 2020 PJZ9n.
 *
 * This file is part of PluginTemplate.
 *
 * PluginTemplate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PluginTemplate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PluginTemplate.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace PJZ9n\QuitMessage;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{
    
    public function onEnable(): void
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        
        PermissionManager::getInstance()->addPermission(new Permission(
            "quitmessage.command.quitmessage",
            null,
            Permission::DEFAULT_OP
        ));
        
        $command = new PluginCommand("quitmessage", $this);
        $command->setUsage("/quitmessage");
        $command->setDescription("Quit時に送信するメッセージを設定する");
        $command->setAliases([
            "qm",
        ]);
        $command->setPermission("quitmessage.command.quitmessage");
        $this->getServer()->getCommandMap()->register("quitmessage", $command);
    }
    
    public function onDisable(): void
    {
        $this->saveConfig();//Configをセーブ
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        //プレイヤーか確認
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "このコマンドはプレイヤーから実行してください。");
            return true;
        }
        
        //フォーム作成
        $form = new CustomForm(function (Player $player, $data): void {
            //受信したときの処理
            if ($data === null) {
                //フォームが閉じられた
                return;//ここで終了
            }
            
            $message = $data[0];
            $this->getConfig()->set("message", $message);
            $player->sendMessage("メッセージを {$message} に設定しました！");
        });
        $form->setTitle("Quit時のメッセージ設定");
        $form->addInput("Quit時のメッセージ", "ここに入力してください", strval($this->getConfig()->get("message")));
        $sender->sendForm($form);
        
        return true;
    }
    
    /**
     * @param PlayerQuitEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $message = $this->getConfig()->get("message");
        $message = str_replace("{name}", $event->getPlayer()->getName(), $message);
        $this->getServer()->broadcastMessage($message);
    }
    
}