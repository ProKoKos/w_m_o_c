<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MinerSyncController extends Controller
{
    // Использование кэша Laravel для хранения сообщений и команд в памяти.
    // Ключи кэша будут включать wmoc_assigned_id майнера.

    public function sync(Request $request)
    {
        $minerId = $request->input("wmoc_assigned_id"); // Предполагаем, что майнер передает свой ID
        if (!$minerId) {
            // Если ID не передан, можно сгенерировать временный или вернуть ошибку
            // Для простоты пока будем использовать общий ключ, если ID нет
            $minerId = "unknown_miner"; 
        }

        $receivedData = $request->json()->all();

        // 1. Сохраняем полученные данные (статус майнера и отчет о команде, если есть)
        $minerMessagesKey = "miner_messages_" . $minerId;
        $messages = Cache::get($minerMessagesKey, []);
        $messages[] = [
            "timestamp" => now()->toIso8601String(),
            "type" => "received_from_miner",
            "payload" => $receivedData
        ];
        Cache::put($minerMessagesKey, $messages, now()->addHours(24)); // Хранить 24 часа

        // 2. Проверяем, есть ли команда для этого майнера
        $minerCommandsKey = "miner_commands_" . $minerId;
        $pendingCommands = Cache::get($minerCommandsKey, []);
        $commandToSend = null;

        if (!empty($pendingCommands)) {
            $commandToSend = array_shift($pendingCommands); // Берем первую команду из очереди
            Cache::put($minerCommandsKey, $pendingCommands, now()->addHours(24)); // Обновляем очередь
            
            // Логируем отправку команды
            $messages[] = [
                "timestamp" => now()->toIso8601String(),
                "type" => "sent_to_miner",
                "payload" => $commandToSend
            ];
            Cache::put($minerMessagesKey, $messages, now()->addHours(24));
        }

        // 3. Формируем ответ серверу
        $responsePayload = [
            "status" => "success",
            "message" => "Data received",
            "server_timestamp_utc" => now()->toIso8601String(),
            "next_command" => $commandToSend // Будет null, если команд нет
        ];

        return response()->json($responsePayload);
    }

    // Вспомогательный метод для добавления команды в очередь (будет вызываться из дашборда)
    // Этот метод не будет доступен как HTTP endpoint напрямую от майнера
    public static function addCommandForMiner($minerId, $commandDetails)
    {
        if (!$minerId) {
            $minerId = "unknown_miner";
        }
        $minerCommandsKey = "miner_commands_" . $minerId;
        $pendingCommands = Cache::get($minerCommandsKey, []);
        
        // Добавляем уникальный ID к команде, чтобы ее можно было отслеживать
        $commandWithId = array_merge(["wmoc_command_id" => Str::uuid()->toString()], $commandDetails);
        $pendingCommands[] = $commandWithId;
        Cache::put($minerCommandsKey, $pendingCommands, now()->addHours(24));

        // Логируем добавление команды в очередь (для дашборда)
        $minerMessagesKey = "miner_messages_" . $minerId;
        $messages = Cache::get($minerMessagesKey, []);
        $messages[] = [
            "timestamp" => now()->toIso8601String(),
            "type" => "command_queued_by_server",
            "payload" => $commandWithId
        ];
        Cache::put($minerMessagesKey, $messages, now()->addHours(24));

        return $commandWithId["wmoc_command_id"];
    }

    // Вспомогательный метод для получения сообщений (для дашборда)
    public static function getMinerMessages($minerId)
    {
        if (!$minerId) {
            $minerId = "unknown_miner";
        }
        $minerMessagesKey = "miner_messages_" . $minerId;
        return Cache::get($minerMessagesKey, []);
    }

    // Метод для очистки сообщений и команд (для отладки/дашборда)
    public static function clearMinerData($minerId)
    {
        if (!$minerId) {
            $minerId = "unknown_miner";
        }
        Cache::forget("miner_messages_" . $minerId);
        Cache::forget("miner_commands_" . $minerId);
    }
}

