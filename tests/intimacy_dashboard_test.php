<?php
declare(strict_types=1);

use function ChimMindPoisoning\dashboardBuildInteractions;
use function ChimMindPoisoning\dashboardNormalizedOutcome;
use function ChimMindPoisoning\dashboardParseLogLine;
use function ChimMindPoisoning\renderDashboard;

require_once __DIR__ . '/fixtures/relationship_manager.cf5030f15781637498be86debe26fcf102f5690d.php';
require_once __DIR__ . '/../server/dashboard_data.php';
require_once __DIR__ . '/../server/dashboard_view.php';

function intimacyDashboardAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function intimacyDashboardLine(array $fields): string
{
    return '[2026-10-19 12:00:00] [info] ' . json_encode(array_merge([
        'schema_version' => 1,
        'plugin' => 'mind_poisoning',
        'version' => '0.1.25',
        'timestamp' => '2026-10-19T12:00:00Z',
        'request_id' => 'abcdef0123456789abcdef01',
        'level' => 'info',
        'event' => 'intimacy_finished',
        'event_id' => 100,
        'playthrough_id' => '1',
        'speaker_id' => 11,
        'speaker_kind' => 'npc',
        'opinion_owner_id' => 11,
        'source_kind' => 'intimacy',
        'subject' => 'npc:22',
        'utterance_id' => 'utt_intimacy_0001',
        'stage' => 'terminal',
        'outcome' => 'committed',
        'reason' => 'committed',
        'model_ms' => 17.2,
    ], $fields), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

$parsed = dashboardParseLogLine(intimacyDashboardLine([
    'listener_id' => 22,
    'speech_hash' => str_repeat('a', 64),
    'evidence' => 'private report text',
    'raw_text' => 'private report text',
]));
intimacyDashboardAssert(
    is_array($parsed)
        && ($parsed['source_kind'] ?? null) === 'intimacy'
        && ($parsed['opinion_owner_id'] ?? null) === '11'
        && !array_key_exists('listener_id', $parsed)
        && ($parsed['subject'] ?? null) === 'npc:22'
        && !isset($parsed['speech_hash'], $parsed['evidence'], $parsed['raw_text']),
    'Direct terminal logs should preserve speaker ownership and target token without exposing hashes or utterance text.'
);
$mismatchedOwner = dashboardParseLogLine(intimacyDashboardLine(['opinion_owner_id' => 22]));
intimacyDashboardAssert($mismatchedOwner === null, 'A direct intimacy log with a different owner and speaker must be rejected.');

$source = [
    'event_id' => 100,
    'utterance_id' => 'utt_intimacy_0001',
    'source_data' => 'Aela: I ended the arrangement with Lydia. (Talking to Lydia)',
    'delivery_state' => 'spoken',
];
$terminal = $parsed;
$persistence = [
    'schema_version' => 1,
    'plugin' => 'mind_poisoning',
    'version' => '0.1.25',
    'timestamp' => '2026-10-19T12:00:00Z',
    'request_id' => 'abcdef0123456789abcdef01',
    'level' => 'info',
    'event' => 'persistence_finished',
    'event_id' => '100',
    'playthrough_id' => '1',
    'speaker_id' => '11',
    'speaker_kind' => 'npc',
    'opinion_owner_id' => '11',
    'source_kind' => 'intimacy',
    'utterance_id' => 'utt_intimacy_0001',
    'stage' => 'commit',
    'outcome' => 'committed',
    'persistence_outcome' => 'committed',
    'persistence_reason' => 'committed',
    'commit_state' => 'confirmed',
    'committed' => true,
    'persistence_ms' => 4.1,
    'changed_count' => 0,
    'arrangement_changed_count' => 1,
    'arrangement_changes' => [[
        'subject' => 'npc:22',
        'basis' => 'direct_npc_dialogue',
        'before' => 'romantic',
        'after' => 'none',
        'proposal' => 'none',
        'outcome' => 'ended',
    ]],
];
$ordinarySkipped = [
    'schema_version' => 1,
    'plugin' => 'mind_poisoning',
    'version' => '0.1.25',
    'timestamp' => '2026-10-19T12:00:00Z',
    'request_id' => 'abcdef0123456789abcdef01',
    'level' => 'info',
    'event' => 'request_finished',
    'event_id' => '100',
    'playthrough_id' => '1',
    'speaker_id' => '11',
    'speaker_kind' => 'npc',
    'listener_id' => '22',
    'utterance_id' => 'utt_intimacy_0001',
    'outcome' => 'skipped',
    'reason' => 'no-subjects',
];
$arrangementEvent = [
    'event_id' => 100,
    'utterance_id' => 'utt_intimacy_0001',
    'judgments' => [],
    'source_kind' => 'intimacy',
    'arrangement_changes' => [[
        'subject' => 'npc:22',
        'basis' => 'direct_npc_dialogue',
        'before' => 'romantic',
        'after' => 'none',
        'proposal' => 'none',
        'outcome' => 'ended',
    ]],
];
$arrangementRecord = [
    'owner_id' => 11,
    'target' => 'npc:22',
    'playthrough_id' => '1',
    'kind' => 'none',
    'prior_kind' => 'romantic',
    'basis' => 'direct_npc_dialogue',
    'sources' => [[
        'event_id' => 100,
        'utterance_id' => 'utt_intimacy_0001',
        'speech_hash' => hash('sha256', 'I ended the arrangement with Lydia.'),
    ]],
    'gamets' => 25.0,
];
$uncertainPersistence = array_merge($persistence, [
    'request_id' => 'abcdef0123456789abcdef02',
    'event_id' => '101',
    'utterance_id' => 'utt_intimacy_0002',
    'outcome' => 'unconfirmed',
    'persistence_outcome' => 'unconfirmed',
    'commit_state' => 'unconfirmed',
    'committed' => false,
    'arrangement_changed_count' => 0,
]);
$uncertainTerminal = array_merge($terminal, [
    'request_id' => 'abcdef0123456789abcdef02',
    'event_id' => '101',
    'utterance_id' => 'utt_intimacy_0002',
    'outcome' => 'unconfirmed',
    'reason' => 'unconfirmed',
]);
$database = [
    'active_playthrough' => '1',
    'player_name' => 'Dragonborn',
    'identities' => ['11' => 'Aela', '22' => 'Lydia'],
    'rows' => [[
        'id' => '11',
        'npc_name' => 'Aela',
        'extended_data' => (object)['relationships' => (object)['Lydia' => (object)['aff' => 8, 'type' => 'ally']]],
        'plugin_extended_data' => (object)['mind_poisoning' => (object)[
            'playthrough_id' => '1',
            'floor_event_id' => 0,
            'events' => [$arrangementEvent],
            'arrangements' => (object)['npc:22' => (object)$arrangementRecord],
        ]],
    ]],
];
$limited = false;
$interactions = dashboardBuildInteractions($database, [$persistence, $terminal, $ordinarySkipped, $uncertainPersistence, $uncertainTerminal], $limited);
$direct = array_values(array_filter($interactions, static fn(array $interaction): bool => ($interaction['source_kind'] ?? null) === 'intimacy'));
$unconfirmed = array_values(array_filter($direct, static fn(array $interaction): bool => ($interaction['event_id'] ?? null) === '101'));
$committed = array_values(array_filter($direct, static fn(array $interaction): bool => ($interaction['event_id'] ?? null) === '100'));
intimacyDashboardAssert(count($direct) === 2 && count($unconfirmed) === 1 && count($committed) === 1 && !$limited, 'The direct records should remain separate from the ordinary no-subject ACK summary.');
intimacyDashboardAssert(
    ($committed[0]['speaker'] ?? null) === 'Aela'
        && ($committed[0]['opinion_owner'] ?? null) === 'Aela'
        && ($committed[0]['listener'] ?? null) === null
        && ($committed[0]['outcome'] ?? null) === 'committed',
    'The direct card must attribute the report to its speaker and show its own committed result.'
);
intimacyDashboardAssert(
    ($committed[0]['arrangement_changes'][0]['label'] ?? null) === 'Lydia'
        && ($committed[0]['arrangement_changes'][0]['before'] ?? null) === 'romantic'
        && ($committed[0]['arrangement_changes'][0]['after'] ?? null) === 'none'
        && ($committed[0]['arrangement_changes'][0]['outcome'] ?? null) === 'ended'
        && ($committed[0]['arrangement_changed_count'] ?? null) === 1,
    'The direct history should show the directed arrangement transition independently of affinity and relationship type.'
);
intimacyDashboardAssert(
    ($committed[0]['changes'] ?? null) === []
        && $database['rows'][0]['extended_data']->relationships->Lydia->aff === 8
        && $database['rows'][0]['extended_data']->relationships->Lydia->type === 'ally',
    'An arrangement-only event must not be represented as a core affinity or relationship-type change.'
);
intimacyDashboardAssert(
    ($unconfirmed[0]['outcome'] ?? null) === 'unconfirmed'
        && ($unconfirmed[0]['arrangement_changed_count'] ?? null) === 0
        && ($unconfirmed[0]['arrangement_changes'][0]['before'] ?? null) === null
        && ($unconfirmed[0]['arrangement_changes'][0]['after'] ?? null) === null
        && ($unconfirmed[0]['arrangement_changes'][0]['outcome'] ?? null) === 'unconfirmed',
    'An unconfirmed arrangement write must not expose before/after values or count as applied.'
);
intimacyDashboardAssert(
    dashboardNormalizedOutcome($persistence) === 'committed',
    'A confirmed arrangement-only commit with zero affinity changes must remain a committed diagnostic outcome.'
);

$htmlModel = [
    'version' => '0.1.25',
    'generated_at' => '2026-10-19T12:00:00Z',
    'notices' => [],
    'source' => ['logs' => 'available', 'database' => 'available', 'limited' => false],
    'interactions' => $committed,
    'records' => [],
];
ob_start();
renderDashboard($htmlModel, ['tab' => 'interactions']);
$html = ob_get_clean();
intimacyDashboardAssert(
    str_contains($html, 'Directed intimacy report')
        && str_contains($html, 'Lydia')
        && str_contains($html, 'Romantic')
        && str_contains($html, 'Ended')
        && str_contains($html, "does not establish the target's view"),
    'The rendered read-only history should explain speaker ownership and show the bounded transition.'
);

echo "intimacy dashboard tests passed\n";
