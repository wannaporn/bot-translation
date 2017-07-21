<?php

namespace LineMob\Bot\Translation\Middleware;

use Google\Cloud\Translate\TranslateClient;
use League\Tactician\Middleware;
use LineMob\Bot\Translation\Command\AbstractTranslateCommand;
use LineMob\Bot\Translation\Command\TranslationCommand;

class TranslateMiddleware implements Middleware
{
    /**
     * @var TranslateClient
     */
    private $client;

    public function __construct(TranslateClient $client) {
        $this->client = $client;
    }

    /**
     * @param AbstractTranslateCommand $command
     * @throws \Exception
     */
    private function fliffTargetLanguage(AbstractTranslateCommand $command)
    {
        $targetLanguageCode = $command->targetLanguageCode;
        $sourceLanguageCode = $this->client->detectLanguage($command->input->text)['languageCode'];

        if ($targetLanguageCode === $sourceLanguageCode) {
            $targetLanguageCode = $command->sourceLanguageCode;
        }

        if ($targetLanguageCode === $sourceLanguageCode) {
            $targetLanguageCode = $command->targetLanguageCode;
        }

        $command->sourceLanguageCode = $sourceLanguageCode;
        $command->targetLanguageCode = $targetLanguageCode;
    }

    /**
     * @param AbstractTranslateCommand $command
     *
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        if (!$command instanceof TranslationCommand) {
            return $next($command);
        }

        $this->fliffTargetLanguage($command);

        if (2 > mb_strlen($command->input->text)) {
            $command->message = 'กรุณาระบุคำมากกว่า 2 คำ';
        } else {
            $translation = $this->client->translate(
                $command->input->text,
                [
                    'target' => $command->targetLanguageCode,
                    'model' => 'base',
                ]
            );

            $command->message = $translation['text'];
        }

        return $next($command);
    }
}
