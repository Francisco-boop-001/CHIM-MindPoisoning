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

$reflectionSubjects = [
    'npc:22' => ['id' => 22, 'name' => 'Lydia'],
    'npc:23' => ['id' => 23, 'name' => 'Jenassa'],
];
$reflectionTypes = ['npc:22' => 'neutral', 'npc:23' => 'neutral'];
$arrangementOnlySubjects = ['npc:23'];
$reflectionEventForPrompt = [
    'source_kind' => 'reflection', 'opinion_owner_id' => 11, 'speaker_id' => 11,
    'speaker_name' => 'Aela', 'text' => 'I argued with Lydia, and I have a casual arrangement with Jenassa.',
    'player_name' => 'Dragonborn',
];
$reflectionOwnerForPrompt = [
    'id' => 11, 'npc_name' => 'Aela', 'personality' => 'A cautious companion',
    'extended_data' => ['relationships' => ['Lydia' => ['aff' => 10, 'type' => 'neutral'], 'Jenassa' => ['aff' => 4, 'type' => 'neutral']]],
];
$childPromptMessages = \ChimMindPoisoning\buildReflectionMessages(
    $reflectionEventForPrompt,
    $reflectionOwnerForPrompt,
    $reflectionSubjects,
    [],
    $reflectionTypes,
    true,
    [],
    $arrangementOnlySubjects
);
$childPromptInput = json_decode($childPromptMessages[1]['content'], true, 32, JSON_THROW_ON_ERROR);
same(false, $childPromptInput['untrusted_data']['candidates']['npc:22']['arrangement_change_allowed'] ?? null, 'A blocked child pair must be excluded from arrangement classification in the reflection prompt.');
same(true, $childPromptInput['untrusted_data']['candidates']['npc:23']['arrangement_change_allowed'] ?? null, 'An eligible adult pair in the same reflection batch must retain arrangement classification.');
check(str_contains($childPromptMessages[0]['content'], 'when false, set `arrangement_change` to null'), 'The reflection prompt must explicitly prohibit arrangement classification for a blocked pair.');

$blockedChildResponse = validModelResponse([
    [
        'subject' => 'npc:22', 'subject_mentioned' => true, 'delta' => 1,
        'reason' => 'A valid ordinary opinion.', 'evidence' => 'I argued with Lydia',
        'type_change' => ['from' => 'neutral', 'to' => 'rival'],
        'arrangement_change' => ['status' => 'romantic', 'evidence' => 'I argued with Lydia'],
    ],
    [
        'subject' => 'npc:23', 'subject_mentioned' => true, 'delta' => 0,
        'reason' => 'No new affinity judgment.', 'evidence' => 'Jenassa', 'arrangement_change' => null,
    ],
]);
$blockedChildParsed = \ChimMindPoisoning\parseJudgments(
    $blockedChildResponse,
    $reflectionSubjects,
    $reflectionEventForPrompt['text'],
    $reflectionTypes,
    true,
    [],
    $arrangementOnlySubjects
);
same(1, $blockedChildParsed['npc:22']['delta'], 'A blocked child arrangement proposal must preserve the valid ordinary judgment.');
same(['from' => 'neutral', 'to' => 'rival'], $blockedChildParsed['npc:22']['type_change'], 'A blocked child arrangement proposal must preserve a valid ordinary type change.');
same(false, array_key_exists('arrangement_change', $blockedChildParsed['npc:22']), 'A blocked child arrangement proposal must be stripped before persistence.');
same(null, $blockedChildParsed['npc:23']['arrangement_change'], 'The adult candidate may still return no arrangement change.');
$malformedAdultResponse = validModelResponse([[
    'subject' => 'npc:23', 'subject_mentioned' => true, 'delta' => 0,
    'reason' => 'No new arrangement information.', 'evidence' => 'Jenassa',
    'arrangement_change' => 'romantic',
]]);
try {
    \ChimMindPoisoning\parseJudgments(
        $malformedAdultResponse,
        ['npc:23' => $reflectionSubjects['npc:23']],
        $reflectionEventForPrompt['text'],
        ['npc:23' => $reflectionTypes['npc:23']],
        true,
        [],
        ['npc:23']
    );
    throw new RuntimeException('The strict reflection parser must reject a malformed adult arrangement proposal.');
} catch (\ChimMindPoisoning\JudgmentValidationFailure $error) {
    same('judgment_arrangement_invalid', $error->reasonCode, 'A malformed eligible arrangement proposal must remain invalid.');
}

resetAckLoggingInteraction();
$GLOBALS['runtime_test_intimacy_enabled'] = true;
[$childRegistration, $childAck, $childDb] = intimacyReflectionFixture('I argued with Lydia, and I have a casual arrangement with Jenassa.');
$childDb->npcs[22]['is_child'] = true;
$childDb->npcs[23] = [
    'id' => 23,
    'npc_name' => 'Jenassa',
    'extended_data' => (object)['relationships' => new stdClass()],
    'plugin_extended_data' => new stdClass(),
];
$childOwner = $childDb->npcs[11];
$childOwner['extended_data']->relationships->Lydia->type = 'neutral';
$childOwner['extended_data']->relationships->Jenassa = (object)['aff' => 4, 'type' => 'friend'];
$childOwner['plugin_extended_data']->mind_poisoning->reflection_state->subjects = [];
$childDb->npcs[11] = $childOwner;
$priorChildArrangement = intimacyReflectionField($childOwner['plugin_extended_data']->mind_poisoning->arrangements, 'npc:22');
$childResponse = validModelResponse([
    [
        'subject' => 'npc:22', 'subject_mentioned' => true, 'delta' => 1,
        'reason' => 'A new ordinary opinion.', 'evidence' => 'I argued with Lydia',
        'type_change' => ['from' => 'neutral', 'to' => 'rival'],
        'arrangement_change' => ['status' => 'romantic', 'evidence' => 'I argued with Lydia'],
    ],
    [
        'subject' => 'npc:23', 'subject_mentioned' => true, 'delta' => 0,
        'reason' => 'No new affinity judgment.', 'evidence' => 'Jenassa',
        'arrangement_change' => ['status' => 'casual', 'evidence' => 'I have a casual arrangement with Jenassa.'],
    ],
]);
$childCalls = 0;
$childMessages = [];
$childRecords = [];
$childStatus = mindPoisoningEvaluateReflection(
    $childRegistration,
    $childAck,
    $childDb,
    static fn(array $given, string $_stage): bool => $given === $childRegistration,
    static function (array $messages, int $maxTokens = 1024) use (&$childCalls, &$childMessages, $childResponse): string {
        $childCalls++;
        $childMessages = $messages;
        return $childResponse;
    },
    captureRequestLog($childRecords)
);
same('committed', $childStatus, 'A child-blocked arrangement lane must not prevent the ordinary reflection commit.');
same(1, $childCalls, 'Child filtering must reuse the existing single reflection model call.');
$childModelInput = json_decode($childMessages[1]['content'] ?? '', true, 32, JSON_THROW_ON_ERROR);
same(false, $childModelInput['untrusted_data']['candidates']['npc:22']['arrangement_change_allowed'] ?? null, 'The child pair must be marked arrangement-ineligible in the evaluated prompt.');
same(true, $childModelInput['untrusted_data']['candidates']['npc:23']['arrangement_change_allowed'] ?? null, 'The adult pair must remain arrangement-eligible in the same evaluated prompt.');
same(11, $childDb->npcs[11]['extended_data']->relationships->Lydia->aff, 'Ordinary affinity updates for a child-named subject must still apply.');
same('rival', $childDb->npcs[11]['extended_data']->relationships->Lydia->type, 'Ordinary relationship-type updates for a child-named subject must still apply.');
check(\ChimMindPoisoning\sameJsonValue($priorChildArrangement, intimacyReflectionField($childDb->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements, 'npc:22')), 'The blocked child arrangement record must remain unchanged.');
same('casual', intimacyReflectionField(intimacyReflectionField($childDb->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements, 'npc:23'), 'kind'), 'The adult arrangement in the same batch must persist normally.');

resetAckLoggingInteraction();
$GLOBALS['runtime_test_intimacy_enabled'] = true;
[$ownerChildRegistration, $ownerChildAck, $ownerChildDb] = intimacyReflectionFixture('I argued with Lydia, and I have a casual arrangement with Lydia.');
$ownerChild = $ownerChildDb->npcs[11];
$ownerChild['extended_data']->relationships->Lydia->type = 'neutral';
$ownerChild['plugin_extended_data']->sharmat = (object)['is_child' => true];
$ownerChild['plugin_extended_data']->mind_poisoning->reflection_state->subjects = [];
$ownerChildDb->npcs[11] = $ownerChild;
$ownerArrangementBefore = intimacyReflectionField($ownerChild['plugin_extended_data']->mind_poisoning->arrangements, 'npc:22');
$ownerChildResponse = validModelResponse([[
    'subject' => 'npc:22', 'subject_mentioned' => true, 'delta' => 1,
    'reason' => 'A valid ordinary opinion.', 'evidence' => 'I argued with Lydia',
    'type_change' => ['from' => 'neutral', 'to' => 'rival'],
    'arrangement_change' => ['status' => 'casual', 'evidence' => 'I have a casual arrangement with Lydia.'],
]]);
$ownerChildCalls = 0;
$ownerChildMessages = [];
$ownerChildBudgets = [];
$ownerChildRecords = [];
$ownerChildStatus = mindPoisoningEvaluateReflection(
    $ownerChildRegistration,
    $ownerChildAck,
    $ownerChildDb,
    static fn(array $given, string $_stage): bool => $given === $ownerChildRegistration,
    static function (array $messages, int $maxTokens = 1024) use (&$ownerChildCalls, &$ownerChildMessages, &$ownerChildBudgets, $ownerChildResponse): string {
        $ownerChildCalls++;
        $ownerChildMessages = $messages;
        $ownerChildBudgets[] = $maxTokens;
        return $ownerChildResponse;
    },
    captureRequestLog($ownerChildRecords)
);
same('committed', $ownerChildStatus, 'A recognized child owner must still commit an ordinary reflection judgment.');
same(1, $ownerChildCalls, 'The child-owner ordinary reflection must use its existing single model call.');
$ownerChildPrompt = json_decode($ownerChildMessages[1]['content'] ?? '', true, 32, JSON_THROW_ON_ERROR);
same(11, $ownerChildDb->npcs[11]['extended_data']->relationships->Lydia->aff, 'A child owner’s ordinary affinity update must still apply.');
same('rival', $ownerChildDb->npcs[11]['extended_data']->relationships->Lydia->type, 'A child owner’s ordinary relationship-type update must still apply.');
check(\ChimMindPoisoning\sameJsonValue($ownerArrangementBefore, intimacyReflectionField($ownerChildDb->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements, 'npc:22')), 'A child owner’s existing arrangement must remain unchanged.');
$ownerChildSystem = $ownerChildMessages[0]['content'] ?? '';
check(!str_contains($ownerChildSystem, 'arrangement_change'), 'A reflection with no eligible arrangement pair must omit arrangement instructions and output schema.');
check(!array_key_exists('arrangement_change_allowed', $ownerChildPrompt['untrusted_data']['candidates']['npc:22'] ?? []), 'A reflection with no eligible arrangement pair must omit arrangement eligibility flags.');
same(true, $ownerChildPrompt['untrusted_data']['candidates']['npc:22']['affinity_update_allowed'] ?? null, 'An ordinary pending candidate must retain its affinity-update constraint.');
same([1024], $ownerChildBudgets, 'An ordinary-only child-owner reflection must use the ordinary output-token budget.');

echo "intimacy reflection tests passed\n";
