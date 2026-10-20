<?php
declare(strict_types=1);

require __DIR__ . '/intimacy_state_test.php';

$enginePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-intimacy-store-count-' . getmypid();
if (!is_dir($enginePath) && !mkdir($enginePath) && !is_dir($enginePath)) {
    throw new RuntimeException('Could not create the isolated pause-control fixture.');
}
file_put_contents($enginePath . DIRECTORY_SEPARATOR . 'main.php', "<?php\n");
$GLOBALS['ENGINE_PATH'] = $enginePath . DIRECTORY_SEPARATOR;
$GLOBALS['chim_interaction_generation'] = 1;
$GLOBALS['intimacy_enabled'] = true;
$GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = false;
register_shutdown_function(static function () use ($enginePath): void {
    @unlink($enginePath . DIRECTORY_SEPARATOR . 'main.php');
    @rmdir($enginePath);
});

$reaffirmStore = new IntimacyMemoryStore();
$reaffirmStore->npcs = [
    11 => intimacyCloneRow($memory->npcs[11]),
    22 => intimacyCloneRow($memory->npcs[22]),
];
$reaffirmSource = $event;
$reaffirmSource['event_id'] = 190;
$reaffirmSource['utterance_id'] = 'utt_intimacy_reaffirm_0001';
$reaffirmSource['gamets'] = 26.0;
$reaffirmSource['source_data'] = 'Aela: We still have a casual arrangement. (Talking to Lydia)';
$reaffirmStore->events = [190 => $reaffirmSource];

$reaffirmText = 'We still have a casual arrangement.';
$reaffirmEvent = $intimacyEvent;
$reaffirmEvent['event_id'] = 190;
$reaffirmEvent['utterance_id'] = 'utt_intimacy_reaffirm_0001';
$reaffirmEvent['speech_hash'] = hash('sha256', $reaffirmText);
$reaffirmEvent['text'] = $reaffirmText;
$reaffirmEvent['source_data'] = $reaffirmSource['source_data'];
$reaffirmEvent['gamets'] = 26.0;
$reaffirmEvent['intimacy_change'] = ['status' => 'casual', 'evidence' => $reaffirmText];
$reaffirmEvent['intimacy_expected_record'] = ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord(
    $reaffirmStore->npcs[11],
    'npc:22',
    '1'
);
$reaffirmEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($reaffirmEvent);

$logs = [];
$requestLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$logs): void {
    $row = json_decode($json, true);
    if (is_array($row)) {
        $logs[] = $row;
    }
}, false);
$status = ChimMindPoisoning\persistJudgments($reaffirmEvent, [], [], $reaffirmStore, $requestLog);
if ($status !== 'committed') {
    throw new RuntimeException('A valid same-kind reaffirmation should persist its source receipt.');
}

$ledger = $reaffirmStore->npcs[11]['plugin_extended_data']->mind_poisoning ?? null;
$arrangement = $ledger?->arrangements['npc:22'] ?? null;
if (($arrangement['kind'] ?? null) !== 'casual' || ($arrangement['sources'][0]['event_id'] ?? null) !== 190) {
    throw new RuntimeException('A reaffirmation must retain the latest source provenance.');
}
$lastEvent = $ledger->events[array_key_last($ledger->events)] ?? null;
if (($lastEvent['arrangement_changes'][0]['outcome'] ?? null) !== 'reaffirmed') {
    throw new RuntimeException('The fixed reaffirmation outcome must remain in the receipt history.');
}
$persistenceLog = array_values(array_filter(
    $logs,
    static fn(array $row): bool => ($row['event'] ?? null) === 'persistence_finished'
))[0] ?? [];
if (($persistenceLog['arrangement_changed_count'] ?? null) !== 0) {
    throw new RuntimeException('A same-kind reaffirmation must not count as a semantic arrangement change.');
}
if (($persistenceLog['persistence_reason'] ?? null) !== 'zero-change') {
    throw new RuntimeException('Receipt-only reaffirmation must use the truthful zero-change reason.');
}

fwrite(STDOUT, "intimacy store count tests passed\n");
