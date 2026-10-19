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
    public int $eventReadCount = 0;

    public function activePlaythrough(): ?array { return ['id' => '1', 'player_name' => 'Dragonborn']; }
    public function acknowledgedEvent(string $utteranceId): ?array { return null; }
    public function playerInputEvent(array $source): ?array { return null; }
    public function eventById(int $eventId, string $utteranceId): ?array
    {
        $this->eventReadCount++;
        $event = $this->events[$eventId] ?? null;
        return is_array($event) && ($event['utterance_id'] ?? null) === $utteranceId ? $event : null;
    }
    public function npcIdentities(): array
    {
        return array_values(array_map(static fn(array $row): array => [
            'id' => $row['id'], 'npc_name' => $row['npc_name'],
        ], $this->npcs));
    }
    public function npcById(int $npcId, bool $forUpdate = false): ?array { return $this->npcs[$npcId] ?? null; }
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
$memoContext = ChimMindPoisoning\mindPoisoningIntimacyContext('Aela', $memoStore);
intimacyContextCheck(substr_count($memoContext, '- private reflection:') === 8, 'The context projection must keep its eight-record bound.');
intimacyContextCheck($memoStore->eventReadCount === 24,
    'Eight context records sharing 24 source rows should perform 24 source reads, not 192; got ' . $memoStore->eventReadCount . '.');

$memoStore->eventReadCount = 0;
unset($memoStore->events[$memoSources[0]['event_id']]);
$prunedProjection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memoStore);
intimacyContextCheck(($prunedProjection['state'] ?? null) === 'unavailable', 'The public API must fail closed after a source row is pruned.');
intimacyContextCheck($memoStore->eventReadCount === 1, 'The public API must perform a fresh source-row read after the context render.');

fwrite(STDOUT, "intimacy context tests passed\n");
