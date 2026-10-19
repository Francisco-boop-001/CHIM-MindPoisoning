<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

require_once __DIR__ . '/intimacy.php';

if (function_exists('chimRegisterPromptInjection')) {
    \chimRegisterPromptInjection(
        'character_bottom',
        'mind_poisoning.intimacy',
        static function (string $_slot, array $context): string {
            $actorName = $context['herika_name'] ?? null;
            if (!is_string($actorName) || trim($actorName) === '') {
                return '';
            }
            return mindPoisoningIntimacyContext($actorName);
        },
        100
    );
}
