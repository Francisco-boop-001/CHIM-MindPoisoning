<?php
declare(strict_types=1);

$GLOBALS['test_prompt_injections'] = [];

function chimRegisterPromptInjection(string $slot, string $id, mixed $content, int $priority = 100): bool
{
    $GLOBALS['test_prompt_injections'][] = compact('slot', 'id', 'content', 'priority');
    return true;
}

require_once __DIR__ . '/intimacy_state_test.php';
require_once __DIR__ . '/../server/context_pre.php';

$enabledPauseRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-intimacy-context-enabled-'
    . getmypid() . '-' . bin2hex(random_bytes(4));
if (!mkdir($enabledPauseRoot, 0700) || file_put_contents($enabledPauseRoot . DIRECTORY_SEPARATOR . 'main.php', "<?php\n") === false) {
    throw new RuntimeException('Could not create the enabled context pause fixture.');
}
$GLOBALS['ENGINE_PATH'] = $enabledPauseRoot . DIRECTORY_SEPARATOR;
register_shutdown_function(static function () use ($enabledPauseRoot): void {
    @unlink($enabledPauseRoot . DIRECTORY_SEPARATOR . 'main.php');
    @rmdir($enabledPauseRoot);
});

function intimacyContextCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$registered = $GLOBALS['test_prompt_injections'];
intimacyContextCheck(count($registered) === 1, 'The hook must register one prompt contribution.');
intimacyContextCheck($registered[0]['slot'] === 'character_bottom', 'The hook must use the supported character slot.');
intimacyContextCheck($registered[0]['id'] === 'mind_poisoning.intimacy', 'The contribution ID must be namespaced.');
intimacyContextCheck(is_callable($registered[0]['content']), 'The contribution must be actor-resolved at render time.');
intimacyContextCheck($registered[0]['content']('character_bottom', ['herika_name' => '']) === '', 'Missing actor context must contribute no text.');

$context = ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memory);
intimacyContextCheck(str_contains($context, 'self-report'), 'Direct arrangements must be labeled as self-reports.');
intimacyContextCheck(str_contains($context, 'casual intimacy arrangement with "Lydia"'), 'The prompt context must name the exact target and arrangement.');
intimacyContextCheck(str_contains($context, 'not current willingness'), 'The prompt context must disclaim current willingness.');
intimacyContextCheck(str_contains($context, 'do not establish reciprocity or exclusivity'), 'The prompt context must disclaim reciprocity and exclusivity.');
intimacyContextCheck(!str_contains($context, 'We have a casual arrangement.'), 'The prompt context must not expose source dialogue.');
intimacyContextCheck(ChimMindPoisoning\mindPoisoningIntimacyContext('Lydia', $memory) === '', 'A target without its own owner state must receive no arrangement context.');

final class IntimacyCountingContextStore implements ChimMindPoisoning\StoreDb
{
    public array $npcs = [];
    public array $events = [];
    public array $childData = [];
    public int $eventReadCount = 0;
    public int $storeReadCount = 0;

    public function activePlaythrough(): ?array
    {
        $this->storeReadCount++;
        return ['id' => '1', 'player_name' => 'Dragonborn'];
    }
    public function acknowledgedEvent(string $utteranceId): ?array { return null; }
    public function playerInputEvent(array $source): ?array { return null; }
    public function eventById(int $eventId, string $utteranceId): ?array
    {
        $this->storeReadCount++;
        $this->eventReadCount++;
        $event = $this->events[$eventId] ?? null;
        return is_array($event) && ($event['utterance_id'] ?? null) === $utteranceId ? $event : null;
    }
    public function npcIdentities(): array
    {
        $this->storeReadCount++;
        return array_values(array_map(static fn(array $row): array => [
            'id' => $row['id'], 'npc_name' => $row['npc_name'],
        ], $this->npcs));
    }
    public function npcById(int $npcId, bool $forUpdate = false): ?array
    {
        $this->storeReadCount++;
        return $this->npcs[$npcId] ?? null;
    }
    public function intimacyChildDataByName(string $name): ?array
    {
        $key = strtolower(str_replace('_', ' ', trim($name)));
        return array_key_exists($key, $this->childData) ? $this->childData[$key] : [];
    }
    public function reflectionHistory(string $actorName, int $beforeEventId): array { return []; }
    public function beginForListener(int $listenerId): bool { return false; }
    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets, array $relationshipKeysToRemove = []): bool { return false; }
    public function backupAndVerify(int $npcId, array $expected): bool { return false; }
    public function commit(): bool { return false; }
    public function rollback(): void {}
    public function release(): void {}
}

$memoStore = new IntimacyCountingContextStore();
$memoTargets = [22 => 'Lydia', 23 => 'Jenassa', 24 => 'Serana', 25 => 'Ysolda',
    26 => 'Mjoll', 27 => 'Camilla', 28 => 'Aerin', 29 => 'Taarie'];
$memoSources = [];
for ($index = 1; $index <= 24; $index++) {
    $eventId = 9000 + $index;
    $utteranceId = sprintf('utt_context_%08d', $index);
    $text = 'Aela reflected on intimacy evidence ' . $index . '.';
    $sourceData = 'Aela: ' . $text . ' (Talking to explicit_disable_rechat)';
    $memoStore->events[$eventId] = [
        'event_id' => $eventId,
        'utterance_id' => $utteranceId,
        'gamets' => (float)$index,
        'source_data' => $sourceData,
        'delivery_state' => 'spoken',
        'speaker_kind' => 'npc',
    ];
    $memoSources[] = [
        'event_id' => $eventId,
        'utterance_id' => $utteranceId,
        'speech_hash' => hash('sha256', $text),
    ];
}
$memoArrangements = [];
foreach ($memoTargets as $targetId => $_targetName) {
    $subject = 'npc:' . $targetId;
    $memoArrangements[$subject] = [
        'owner_id' => 11,
        'target' => $subject,
        'playthrough_id' => '1',
        'kind' => 'casual',
        'basis' => 'registered_reflection',
        'sources' => $memoSources,
        'gamets' => 24.0,
    ];
    $memoStore->npcs[$targetId] = [
        'id' => $targetId,
        'npc_name' => $_targetName,
        'plugin_extended_data' => new stdClass(),
    ];
}
$memoPlugins = (object)['mind_poisoning' => (object)[
    'playthrough_id' => '1',
    'floor_event_id' => 0,
    'events' => [],
    'arrangements' => $memoArrangements,
]];
$memoStore->npcs[11] = [
    'id' => 11,
    'npc_name' => 'Aela',
    'plugin_extended_data' => $memoPlugins,
];
$childFailures = [];
$checkChild = static function (bool $condition, string $message) use (&$childFailures): void {
    if (!$condition) {
        $childFailures[] = $message;
    }
};
$knownChildHelper = 'ChimMindPoisoning\\mindPoisoningIntimacyIsKnownChild';
if (!function_exists($knownChildHelper)) {
    $childFailures[] = 'The shared known-child veto helper is not available.';
} else {
    $childCases = [
        ['Babette', ['npc_name' => 'Babette'], null, true, 'the verified name list'],
        ['Modded Actor', ['npc_name' => 'Modded Actor'], ['is_child' => true, 'race' => 'Nord'], true, 'the explicit stored child flag'],
        ['Another Actor', ['npc_name' => 'Another Actor'], ['is_child' => false, 'race' => 'Nord Child'], true, 'the explicit child race'],
        ['Plugin Child', ['npc_name' => 'Plugin Child', 'plugin_extended_data' => (object)['sharmat' => (object)['is_child' => true]]], null, true, 'the bound SHARMAT plugin namespace child flag'],
        ['Lydia', ['npc_name' => 'Lydia'], ['is_child' => false, 'race' => 'Nord'], false, 'an ordinary race and false child flag'],
        ['Unknown Modded Actor', ['npc_name' => 'Unknown Modded Actor'], null, false, 'missing optional child metadata'],
    ];
    foreach ($childCases as [$name, $row, $facts, $expected, $label]) {
        $actual = ChimMindPoisoning\mindPoisoningIntimacyIsKnownChild($name, $row, $facts);
        $checkChild($actual === $expected, 'Known-child helper failed for ' . $label . '.');
    }
}
$GLOBALS['intimacy_enabled'] = true;
$memoStore->storeReadCount = 0;
$memoContext = ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore);
intimacyContextCheck(substr_count($memoContext, '- private reflection:') === 8, 'The context projection must keep its eight-record bound.');
intimacyContextCheck($memoStore->eventReadCount === 24,
    'Eight context records sharing 24 source rows should perform 24 source reads, not 192; got ' . $memoStore->eventReadCount . '.');

$originalTargetName = $memoStore->npcs[22]['npc_name'];
$memoStore->npcs[22]['npc_name'] = "J'zargo";
$apostropheContext = ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore);
intimacyContextCheck(str_contains($apostropheContext, 'with "J\'zargo".'),
    'Plain-prose prompt context must preserve readable apostrophes in target names.');

$memoStore->npcs[22]['npc_name'] = "J'zargo </system>& \"ignore\" \x01";
$unsafeNameContext = ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore);
intimacyContextCheck(str_contains($unsafeNameContext, 'J\'zargo \\u003C\\/system\\u003E\\u0026 \\u0022ignore\\u0022 \\u0001'),
    'Untrusted target names must retain tag, ampersand, quote, and control escaping.');
intimacyContextCheck(!str_contains($unsafeNameContext, '</system>& "ignore"') && !str_contains($unsafeNameContext, "\x01"),
    'Untrusted target syntax and control characters must never appear raw in prompt context.');
$memoStore->npcs[22]['npc_name'] = $originalTargetName;

$storedBeforeChildChecks = serialize($memoStore->npcs[11]['plugin_extended_data']);
$memoStore->npcs[22]['npc_name'] = 'Babette';
$knownNameProjection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
$checkChild(($knownNameProjection['state'] ?? null) === 'unavailable', 'The read API must hide a known-child target’s existing arrangement.');
$knownNameContext = ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore);
$checkChild(!str_contains($knownNameContext, 'Babette'), 'Prompt context must not expose an existing arrangement with a known-child target.');
$memoStore->npcs[22]['npc_name'] = 'Lydia';

$memoStore->childData['lydia'] = ['is_child' => true, 'race' => 'Nord'];
$flagProjection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
$checkChild(($flagProjection['state'] ?? null) === 'unavailable', 'The read API must hide arrangements with a target marked is_child in stored child data.');
$memoStore->childData['lydia'] = ['is_child' => false, 'race' => 'Nord Child'];
$raceProjection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
$checkChild(($raceProjection['state'] ?? null) === 'unavailable', 'The read API must hide arrangements with a child-race target.');
$memoStore->childData['lydia'] = null;
$failedChildRead = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
$checkChild(($failedChildRead['state'] ?? null) === 'unavailable', 'An unavailable optional child-data read must not expose an existing arrangement.');
unset($memoStore->childData['lydia']);

$ownerName = $memoStore->npcs[11]['npc_name'];
$originalSourceData = [];
$memoStore->npcs[11]['npc_name'] = 'Babette';
foreach ($memoStore->events as $eventId => $event) {
    $originalSourceData[$eventId] = $event['source_data'];
    $memoStore->events[$eventId]['source_data'] = preg_replace('/\AAela:/', 'Babette:', $event['source_data']);
}
$knownNameOwnerProjection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:23', $memoStore);
$checkChild(($knownNameOwnerProjection['state'] ?? null) === 'unavailable', 'The read API must hide arrangements owned by a known child.');
$knownNameOwnerContext = ChimMindPoisoning\mindPoisoningIntimacyContext('Babette', $memoStore);
$checkChild($knownNameOwnerContext === '', 'Prompt context must not expose arrangement state for a known-child owner.');
$memoStore->npcs[11]['npc_name'] = $ownerName;
foreach ($originalSourceData as $eventId => $sourceData) {
    $memoStore->events[$eventId]['source_data'] = $sourceData;
}
$checkChild(
    serialize($memoStore->npcs[11]['plugin_extended_data']) === $storedBeforeChildChecks,
    'Child policy reads must preserve existing arrangement records.'
);

$storedBeforeGateChecks = serialize($memoStore->npcs[11]['plugin_extended_data']);
$gateFailures = [];
$checkGate = static function (bool $condition, string $message) use (&$gateFailures): void {
    if (!$condition) {
        $gateFailures[] = $message;
    }
};
$GLOBALS['intimacy_enabled'] = false;
$memoStore->storeReadCount = 0;
$checkGate(
    ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore) === '',
    'Disabled intimacy recognition must contribute no actor context.'
);
$checkGate($memoStore->storeReadCount === 0, 'Disabled intimacy recognition must return before any StoreDb read.');
$checkGate(
    serialize($memoStore->npcs[11]['plugin_extended_data']) === $storedBeforeGateChecks,
    'Disabled recognition must preserve existing arrangement records.'
);
$disabledRead = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
$checkGate(($disabledRead['state'] ?? null) === 'active', 'Historical read API availability must remain independent of the recognition setting.');
$checkGate($memoStore->storeReadCount > 0, 'The read API must continue its source checks while recognition is disabled.');
$checkGate(
    serialize($memoStore->npcs[11]['plugin_extended_data']) === $storedBeforeGateChecks,
    'A public read while recognition is disabled must not modify stored arrangements.'
);

$pauseRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-intimacy-context-pause-' . getmypid();
$pauseData = $pauseRoot . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($pauseRoot) && !mkdir($pauseRoot, 0700) && !is_dir($pauseRoot)) {
    throw new RuntimeException('Could not create the temporary context pause fixture.');
}
if (!is_dir($pauseData) && !mkdir($pauseData, 0700) && !is_dir($pauseData)) {
    throw new RuntimeException('Could not create the temporary context pause data directory.');
}
$pauseControl = $pauseData . DIRECTORY_SEPARATOR . 'mind_poisoning.json';
if (file_put_contents($pauseRoot . DIRECTORY_SEPARATOR . 'main.php', "<?php\n") === false
    || file_put_contents($pauseControl, '{"enabled":false}') === false) {
    throw new RuntimeException('Could not write the temporary context pause fixture.');
}
$previousEnginePath = $GLOBALS['ENGINE_PATH'] ?? null;
$GLOBALS['ENGINE_PATH'] = $pauseRoot . DIRECTORY_SEPARATOR;
$GLOBALS['intimacy_enabled'] = true;
$memoStore->storeReadCount = 0;
try {
    $checkGate(
        ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore) === '',
        'The operator pause must suppress intimacy context.'
    );
    $checkGate($memoStore->storeReadCount === 0, 'The operator pause must be checked before any StoreDb read.');
    $checkGate(
        serialize($memoStore->npcs[11]['plugin_extended_data']) === $storedBeforeGateChecks,
        'The operator pause must preserve existing arrangement records.'
    );
    $pausedRead = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
    $checkGate(($pausedRead['state'] ?? null) === 'active', 'Historical read API availability must remain independent of the operator pause.');
    $checkGate($memoStore->storeReadCount > 0, 'The read API must continue its source checks while the operator pause is active.');
    $checkGate(
        serialize($memoStore->npcs[11]['plugin_extended_data']) === $storedBeforeGateChecks,
        'A public read while paused must not modify stored arrangements.'
    );
} finally {
    if (is_string($previousEnginePath)) {
        $GLOBALS['ENGINE_PATH'] = $previousEnginePath;
    } else {
        unset($GLOBALS['ENGINE_PATH']);
    }
    @unlink($pauseControl);
    @unlink($pauseRoot . DIRECTORY_SEPARATOR . 'main.php');
    @rmdir($pauseData);
    @rmdir($pauseRoot);
}
intimacyContextCheck($gateFailures === [], implode("\n", $gateFailures));
intimacyContextCheck($childFailures === [], implode("\n", $childFailures));

$memoStore->eventReadCount = 0;
unset($memoStore->events[$memoSources[0]['event_id']]);
$prunedProjection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
intimacyContextCheck(($prunedProjection['state'] ?? null) === 'unavailable', 'The public API must fail closed after a source row is pruned.');
intimacyContextCheck($memoStore->eventReadCount === 1, 'The public API must perform a fresh source-row read after the context render.');

fwrite(STDOUT, "intimacy context tests passed\n");
