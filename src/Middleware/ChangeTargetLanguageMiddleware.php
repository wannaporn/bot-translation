<?php

namespace LineMob\Bot\Translation\Middleware;

use Google\Cloud\Translate\TranslateClient;
use League\Tactician\Middleware;
use LineMob\Bot\Translation\Command\AbstractTranslateCommand;
use LineMob\Core\Constants;

class ChangeTargetLanguageMiddleware implements Middleware
{
    /**
     * @var TranslateClient
     */
    private $client;

    public function __construct(TranslateClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    private function getLocales()
    {
        $languages = [];

        foreach ($this->client->localizedLanguages() as $language) {
            $languages[$language['code']] = preg_replace('/ภาษา/', '', $language['name']);
        }

        $c = new \Collator('th_TH');

        uasort($languages, function($a, $b) use ($c) {
            return $c->compare($a, $b);
        });

        return $languages;
    }

    /**
     * @param AbstractTranslateCommand $command
     *
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        if (!$command->supported($command->input->text)) {
            return $next($command);
        }

        $locales = $this->getLocales();
        $locale = null;

        $command->type = Constants::TYPE_TEXT;

        if (preg_match(sprintf('/%s (.*)/', $command->cmd), $command->input->text, $match)) {
            $locale = $match[1];
        }

        $command->logs = sprintf('Selected locale: `%s`', $locale);

        if (!array_key_exists($locale, $locales)) {
            $message = ['กรุณาระบุรหัสภาษาตามรายการนี้'];

            // should provide a link to listing supported languages!
            foreach ($locales as $key => $value) {
                $message[] = sprintf("%s\t: %s", $key, $value);
            }

            $command->message = join("\r\n", $message);
            $command->actived = true;

            return $next($command);
        }

        $command->targetLanguageCode = $locale;
        $command->message = sprintf('Change default language to "%s - %s" was successfully.', $locale, $locales[$locale]);

        return $next($command);
    }
}
