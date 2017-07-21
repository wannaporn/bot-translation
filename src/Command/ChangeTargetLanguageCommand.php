<?php

namespace LineMob\Bot\Translation\Command;

class ChangeTargetLanguageCommand extends AbstractTranslateCommand
{
    protected $cmd = '>';

    public function supported($cmd)
    {
        if (parent::supported($cmd)) {
            return true;
        }

        return preg_match(sprintf('/%s [a-z]{2}/i', $this->cmd), $cmd);
    }
}
