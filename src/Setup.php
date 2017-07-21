<?php

namespace LineMob\Bot\Translation;

use Google\Cloud\Translate\TranslateClient;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use League\Tactician\Plugins\LockingMiddleware;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LineMob\Bot\Translation\Command\ChangeTargetLanguageCommand;
use LineMob\Bot\Translation\Command\TranslationCommand;
use LineMob\Bot\Translation\Middleware\ChangeTargetLanguageMiddleware;
use LineMob\Bot\Translation\Middleware\DefineUserLanguageMiddleware;
use LineMob\Bot\Translation\Middleware\TranslateMiddleware;
use LineMob\Core\Message\CarouselMessage;
use LineMob\Core\Message\Factory;
use LineMob\Core\Message\TextMessage;
use LineMob\Core\Middleware\CleanInputTextMiddleware;
use LineMob\Core\Middleware\ClearActivedCmdMiddleware;
use LineMob\Core\Middleware\DummyStorageConnectMiddleware;
use LineMob\Core\Middleware\DummyStoragePersistMiddleware;
use LineMob\Core\Middleware\DumpLogMiddleware;
use LineMob\Core\Middleware\StoreActivedCmdMiddleware;
use LineMob\Core\Receiver;
use LineMob\Core\Registry;
use LineMob\Core\SenderHandler;

class Setup
{
    /**
     * @param string $lineChannelToken
     * @param string $lineChannelSecret
     * @param array $middleware
     *
     * @return Receiver
     */
    public static function create($lineChannelToken, $lineChannelSecret, array $middlewares = [])
    {
        $linebot = new LINEBot(new CurlHTTPClient($lineChannelToken), ['channelSecret' => $lineChannelSecret]);

        $factory = new Factory();
        $factory->add(new TextMessage());
        $factory->add(new CarouselMessage());

        $handler = new SenderHandler($linebot, $factory);

        $registry = new Registry();
        $registry->add(ChangeTargetLanguageCommand::class, $handler);
        $registry->add(TranslationCommand::class, $handler, true);

        // should be first of all middlewares
        array_unshift($middlewares, new LockingMiddleware());

        // must be end of all middlewares
        array_push($middlewares, new CommandHandlerMiddleware(
            new ClassNameExtractor(),
            new InMemoryLocator($registry->getCommandList()),
            new HandleInflector()
        ));

        $commandBus = new CommandBus($middlewares);

        return new Receiver($linebot, $registry, $commandBus);
    }

    /**
     * @param array $config
     *
     * @return Receiver
     */
    public static function demo(array $config)
    {
        $googleClient = new TranslateClient([
            'projectId' => $config['google_project_id'],
            'key' => $config['google_api_key'],
            'target' => $config['google_default_locale'],
        ]);

        return self::create($config['line_channel_token'], $config['line_channel_secret'], [
            new CleanInputTextMiddleware(),
            new ClearActivedCmdMiddleware(),
            new DummyStorageConnectMiddleware(),
            new StoreActivedCmdMiddleware(),
            new DefineUserLanguageMiddleware($config['google_default_locale'], $config['google_fallback_locale']),
            new ChangeTargetLanguageMiddleware($googleClient),
            new TranslateMiddleware($googleClient),
            new DummyStoragePersistMiddleware(),
            new DumpLogMiddleware($config['isDebug']),
        ]);
    }
}
