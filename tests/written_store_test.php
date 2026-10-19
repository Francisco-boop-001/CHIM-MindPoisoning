<?php
declare(strict_types=1);

require_once __DIR__ . '/../server/store.php';

function writtenStoreSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
    }
}

function writtenStoreReceipt(string $documentId, int $eventId = 15): array
{
    return [
        'event_id' => $eventId,
        'document_id' => $documentId,
        'receipt_id' => '12345678-1234-4234-8234-123456789abc',
        'operation_id' => '22345678-1234-4234-8234-123456789abc',
        'playthrough_id' => '1',
        'recipient_id' => 22,
        'source_digest' => str_repeat('a', 64),
        'outcome' => 'committed',
    ];
}

$documentId = '32345678-1234-4234-8234-123456789abc';
$receipt = writtenStoreReceipt($documentId);
writtenStoreSame(true, ChimMindPoisoning\validStoredWrittenReceipts([$documentId => $receipt], '1', 22), 'A canonical recipient-owned written receipt should validate.');
$sourceRow = [
    'event_id' => '15',
    'source_type' => 'info',
    'source_sess' => 'written_evidence_v1',
    'source_ts' => '1000',
    'source_gamets' => '12.5',
    'gamets' => '12.5',
    'source_localts' => '20',
    'source_people' => '|Recipient|',
    'source_data' => '{}',
];
writtenStoreSame('info', ChimMindPoisoning\normalizeWrittenEvidenceSourceRow($sourceRow)['source_type'] ?? null, 'Written source rows use the globally excluded info event type.');
$legacySourceRow = $sourceRow;
$legacySourceRow['source_type'] = 'written_evidence';
writtenStoreSame(null, ChimMindPoisoning\normalizeWrittenEvidenceSourceRow($legacySourceRow), 'Custom physical event types must not be accepted as written evidence.');
$receipts = ChimMindPoisoning\appendWrittenEvidenceReceipt([], $receipt);
writtenStoreSame([$documentId => $receipt], $receipts, 'A written receipt should be keyed by document UUID.');
writtenStoreSame(false, ChimMindPoisoning\validStoredWrittenReceipts([$documentId => array_merge($receipt, ['outcome' => 'uncertain'])], '1', 22), 'Uncertain commits must never be represented as processed receipts.');
writtenStoreSame(false, ChimMindPoisoning\validStoredWrittenReceipts([$documentId => $receipt], '2', 22), 'A receipt from another playthrough must not validate in the active owner namespace.');
$duplicateRejected = false;
try {
    ChimMindPoisoning\appendWrittenEvidenceReceipt($receipts, $receipt);
} catch (RuntimeException) {
    $duplicateRejected = true;
}
writtenStoreSame(true, $duplicateRejected, 'The same document must not acquire a second owner receipt.');

$fullReceipts = [];
for ($index = 1; $index <= 1024; $index++) {
    $id = sprintf('%08x-0000-4000-8000-%012x', $index, $index);
    $item = writtenStoreReceipt($id, $index);
    $item['receipt_id'] = sprintf('%08x-1111-4111-8111-%012x', $index, $index);
    $item['operation_id'] = sprintf('%08x-2222-4222-8222-%012x', $index, $index);
    $fullReceipts = ChimMindPoisoning\appendWrittenEvidenceReceipt($fullReceipts, $item);
}
$capacityRejected = false;
try {
    ChimMindPoisoning\appendWrittenEvidenceReceipt($fullReceipts, writtenStoreReceipt('42345678-1234-4234-8234-123456789abc', 1025));
} catch (RuntimeException) {
    $capacityRejected = true;
}
writtenStoreSame(true, $capacityRejected, 'The 1024 receipt cap must fail closed without eviction.');
writtenStoreSame(1024, count($fullReceipts), 'No receipt is evicted at capacity.');

$arrangements = ['npc:33' => ['opaque' => 'preserve']];
$reflectionState = ['basis' => str_repeat('b', 64), 'subjects' => ['npc:33']];
$sameScope = ChimMindPoisoning\nextLedger([
    'playthrough_id' => '1',
    'floor_event_id' => 10,
    'events' => [],
    'arrangements' => $arrangements,
    'reflection_state' => $reflectionState,
    'written_receipts' => $receipts,
], '1', 16, 'utt_written_store_0001', []);
writtenStoreSame($arrangements, $sameScope['arrangements'] ?? null, 'Normal ledger writes must preserve the intimacy arrangement namespace.');
writtenStoreSame($reflectionState, $sameScope['reflection_state'] ?? null, 'Normal ledger writes must preserve reflection state.');
writtenStoreSame($receipts, $sameScope['written_receipts'] ?? null, 'Normal ledger writes must preserve written receipts independently of the utterance floor.');

$newScope = ChimMindPoisoning\nextLedger([
    'playthrough_id' => '1',
    'floor_event_id' => 10,
    'events' => [],
    'written_receipts' => $receipts,
], '2', 16, 'utt_written_store_0002', []);
writtenStoreSame(false, array_key_exists('written_receipts', $newScope), 'A new playthrough namespace must not inherit old written receipts.');

echo "written store state tests passed\n";
