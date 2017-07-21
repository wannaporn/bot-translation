<?php

namespace LineMob\Bot\Translation\Middleware;

use League\Tactician\Middleware;
use LineMob\Bot\Translation\Command\AbstractTranslateCommand;

class DefineUserLanguageMiddleware implements Middleware
{
    /**
     * @var string
     */
    private $defaultTargetLanguageCode;

    /**
     * @var string
     */
    private $fallbackTargetLanguageCode;

    public function __construct($defaultTargetLanguageCode = 'th', $fallbackTargetLanguageCode = 'en')
    {
        $this->defaultTargetLanguageCode = $defaultTargetLanguageCode;
        $this->fallbackTargetLanguageCode = $fallbackTargetLanguageCode;
    }

    /**
     * @param AbstractTranslateCommand $command
     *
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        $sourceLanguageCode = $command->sourceLanguageCode ?: $this->defaultTargetLanguageCode;
        $targetLanguageCode = $command->targetLanguageCode ?: $this->fallbackTargetLanguageCode;

        $command->sourceLanguageCode = $sourceLanguageCode;
        $command->targetLanguageCode = $targetLanguageCode;

        return $next($command);
    }
}
