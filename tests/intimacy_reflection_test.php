<?php
declare(strict_types=1);

function chimGetGeneralSettingBool(string $id, bool $default = false): bool
{
    if ($id === 'mind_poisoning_intimacy_enabled') {
        return ($GLOBALS['runtime_test_intimacy_enabled'] ?? false) === true;
    }
    return $id === 'mind_poisoning_overhearing_enabled' ? false : $default;
}

require_once __DIR__ . '/runtime_test.php';
require_once __DIR__ . '/../server/reflection.php';

use function ChimMindPoisoning\mindPoisoningEvaluateReflection;
use function ChimMindPoisoning\reflectionBasisForEvent;

function intimacyReflectionField(mixed $value, string $key): mixed
{
    if (is_array($value)) {
        return $value[$key] ?? null;
    }
    return is_object($value) ? ($value->{$key} ?? null) : null;
}

function intimacyReflectionFixture(string $speech): array
{
    [, , , $db] = baseFixture();
    $actor = $db->npcs[11];
    $actor['extended_data']->relationships->Lydia = (object)['aff' => 10, 'type' => 'ally'];
    $actor['personality'] = 'A cautious companion';
    $db->npcs[11] = $actor;
    $priorText = 'Lydia and I have been together.';
    $priorUtterance = 'utt_intimacy_previous';
    $db->events = [
        99 => [
            'event_id' => 99,
            'utterance_id' => $priorUtterance,
            'gamets' => 9.0,
            'source_data' => 'Aela: ' . $priorText . ' (Talking to explicit_disable_rechat)',
            'delivery_state' => 'spoken',
            'type' => 'chat',
            'speaker_kind' => 'npc',
        ],
        100 => [
            'event_id' => 100,
            'utterance_id' => 'utt_intimacy_current1',
            'gamets' => 10.0,
            'source_data' => 'Aela: ' . $speech . ' (Talking to explicit_disable_rechat)',
            'delivery_state' => 'spoken',
            'type' => 'chat',
            'speaker_kind' => 'npc',
        ],
    ];
    $event = [
        'event_id' => 100,
        'opinion_owner_id' => 11,
        'speaker_name' => 'Aela',
        'player_name' => 'Dragonborn',
    ];
    $history = null;
    $basisReason = null;
    $basis = reflectionBasisForEvent($actor, $db, $event, $history, $basisReason);
    check(is_string($basis), 'The fixture must derive its reflection basis from the production helper.');
    $actor['plugin_extended_data'] = (object)[
        'mind_poisoning' => (object)[
            'playthrough_id' => '1',
            'floor_event_id' => 0,
            'events' => [],
            'reflection_state' => (object)['basis' => $basis, 'subjects' => ['npc:22']],
            'arrangements' => (object)[
                'npc:22' => (object)[
                    'kind' => 'romantic',
                    'owner_id' => 11,
                    'target' => 'npc:22',
                    'playthrough_id' => '1',
                    'basis' => 'registered_reflection',
                    'sources' => [[
                        'event_id' => 99,
                        'utterance_id' => $priorUtterance,
                        'speech_hash' => hash('sha256', $priorText),
                    ]],
                    'gamets' => 9.0,
                ],
            ],
        ],
    ];
    $db->npcs[11] = $actor;
    $utteranceId = 'utt_intimacy_current1';
    $registration = [
        'event_id' => 100,
        'utterance_id' => $utteranceId,
        'actor_id' => 11,
        'actor_name' => 'Aela',
        'playthrough_id' => '1',
        'config_id' => '12345678-1234-4234-8234-123456789abc',
        'rechat_target_hint' => 'explicit_disable_rechat',
        'speech_hash' => hash('sha256', $speech),
    ];
    $ack = ['_speech', 0, 10, json_encode([
        'speaker' => 'Aela',
        'listener' => 'Dragonborn',
        'speech' => $speech,
        'utterance_id' => $utteranceId,
    ], JSON_THROW_ON_ERROR)];
    return [$registration, $ack, $db];
}

$budgetSubjects = [];
$budgetTypes = [];
$budgetJudgments = [];
$longReason = str_repeat('r', 600);
$longAffinityEvidence = str_repeat('e', 600);
$arrangementEvidence = str_repeat('a', 120);
$budgetUtterance = $longReason . $longAffinityEvidence . $arrangementEvidence;
for ($id = 22; $id <= 29; $id++) {
    $token = 'npc:' . $id;
    $budgetSubjects[$token] = ['id' => $id, 'name' => 'Candidate ' . $id];
    $budgetTypes[$token] = 'ally';
    $budgetJudgments[] = [
        'subject' => $token,
        'subject_mentioned' => true,
        'delta' => 0,
        'reason' => $longReason,
        'evidence' => $longAffinityEvidence,
        'arrangement_change' => ['status' => 'romantic', 'evidence' => $arrangementEvidence],
    ];
}
$budgetResponse = json_encode(['judgments' => $budgetJudgments], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
check(strlen($budgetResponse) <= 16384, 'A maximum-shaped eight-candidate intimacy response must fit the production parser byte cap.');
$budgetParsed = \ChimMindPoisoning\parseJudgments($budgetResponse, $budgetSubjects, $budgetUtterance, $budgetTypes, true);
same(8, count($budgetParsed), 'The production parser must accept all eight bounded arrangement judgments in one response.');

resetAckLoggingInteraction();
$GLOBALS['runtime_test_intimacy_enabled'] = true;
[$registration, $ack, $db] = intimacyReflectionFixture('I ended the arrangement with Lydia.');
$calls = 0;
$budgets = [];
$stages = [];
$records = [];
$modelMessages = [];
$response = validModelResponse([[
    'subject' => 'npc:22',
    'subject_mentioned' => true,
    'delta' => 0,
    'reason' => 'No new affinity judgment.',
    'evidence' => 'Lydia',
    'arrangement_change' => ['status' => 'none', 'evidence' => 'I ended the arrangement with Lydia.'],
]]);
$status = mindPoisoningEvaluateReflection(
    $registration,
    $ack,
    $db,
    static function (array $given, string $stage) use (&$stages, $registration): bool {
        $stages[] = $stage;
        return $given === $registration;
    },
    static function (array $messages, int $maxTokens = 1024) use (&$calls, &$budgets, &$modelMessages, $response): string {
        $calls++;
        $budgets[] = $maxTokens;
        $modelMessages = $messages;
        return $response;
    },
    captureRequestLog($records)
);
same('committed', $status, 'A new registered source with unchanged affinity basis may commit its arrangement-only ending.');
same(1, $calls, 'An arrangement ending must reuse one registered reflection model call.');
same([4096], $budgets, 'An opted-in eight-candidate reflection must request the existing bounded 4096-token budget.');
$modelInput = json_decode($modelMessages[1]['content'] ?? '', true);
same(false, $modelInput['untrusted_data']['candidates']['npc:22']['affinity_update_allowed'] ?? null, 'The same-basis prompt must identify this candidate as arrangement-only.');
check(str_contains($modelMessages[0]['content'] ?? '', 'When it is false, set that candidate\'s delta to zero and omit or set `type_change` to null'), 'The same-basis prompt must instruct zero affinity and no type change for arrangement-only candidates.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'An arrangement-only reflection must preserve affinity.');
same('ally', $db->npcs[11]['extended_data']->relationships->Lydia->type, 'An arrangement-only reflection must preserve relationship type.');
$saved = intimacyReflectionField($db->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements ?? null, 'npc:22');
same('none', intimacyReflectionField($saved, 'kind'), 'The registered speaker-owned edge should retain the explicit ending.');
same('romantic', intimacyReflectionField($saved, 'prior_kind'), 'An ending should retain the prior kind for the read API.');
check(in_array('pre_model', $stages, true) && in_array('transaction', $stages, true), 'Existing v1 registration checkpoints must remain in force.');
$reflectionEvents = intimacyReflectionField($db->npcs[11]['plugin_extended_data']->mind_poisoning, 'events');
$currentReceipt = null;
foreach (is_array($reflectionEvents) ? $reflectionEvents : [] as $entry) {
    if ((int)intimacyReflectionField($entry, 'event_id') === 100) {
        $currentReceipt = $entry;
        break;
    }
}
same(100, (int)intimacyReflectionField($currentReceipt, 'event_id'), 'The exact new reflection event must be receipted.');
same($registration['utterance_id'], intimacyReflectionField($currentReceipt, 'utterance_id'), 'The exact registered utterance must be receipted.');

echo "intimacy reflection tests passed\n";
