<?php
declare(strict_types=1);

namespace {
    if (!function_exists('chimIsGlobalLlmConnectorEnabled')) {
        function chimIsGlobalLlmConnectorEnabled(string $setting): bool
        {
            return $setting === 'RELLLM_CONNECTOR' && ($GLOBALS['written_connector_enabled'] ?? false) === true;
        }
    }

    if (!function_exists('chimInteractionBegin')) {
        function chimInteractionBegin(): void
        {
            $GLOBALS['chim_interaction_generation'] ??= 7;
        }
    }

    if (!function_exists('chimInteractionState')) {
        function chimInteractionState(): array
        {
            return ['enabled' => ($GLOBALS['written_interaction_enabled'] ?? true) === true,
                'generation' => $GLOBALS['chim_interaction_generation'] ?? 7];
        }
    }
}

namespace ChimMindPoisoning {
    $apiPath = __DIR__ . '/../server/written_evidence.php';
    if (!is_file($apiPath)) {
        fwrite(STDERR, "FAIL: written_evidence.php API is not implemented yet.\n");
        exit(1);
    }

    require_once __DIR__ . '/../server/store.php';
    require_once __DIR__ . '/../server/controls.php';
    require_once __DIR__ . '/../server/model.php';
    require_once $apiPath;

    final class WrittenEvidenceMemoryStore implements StoreDb, WrittenEvidenceStoreDb, RestorePolicyStoreDb
    {
        public array $npcs = [];
        public array $events = [];
        public array $locks = [];
        public array $writes = [];
        public int $snapshots = 0;
        public int $commits = 0;
        public int $rollbacks = 0;
        public int $sourceReads = 0;
        public int $identityReads = 0;
        public int $boundsReads = 0;
        public bool $commitSucceeds = true;
        public int $latestInitEventId = 0;
        public int $maxEventId = 900;
        public ?\Closure $afterSnapshot = null;
        private array $transactionRows = [];

        public function activePlaythrough(): ?array
        {
            return ['id' => 'unprofiled', 'player_name' => 'TestHero'];
        }

        public function acknowledgedEvent(string $utteranceId): ?array { return null; }
        public function playerInputEvent(array $source): ?array { return null; }
        public function eventById(int $eventId, string $utteranceId): ?array { return null; }

        public function npcIdentities(): array
        {
            return array_values(array_map(static fn(array $row): array => [
                'id' => $row['id'], 'npc_name' => $row['npc_name'],
            ], $this->npcs));
        }

        public function npcById(int $npcId, bool $forUpdate = false): ?array
        {
            if ($forUpdate) {
                $this->locks[] = $npcId;
            }
            return $this->npcs[$npcId] ?? null;
        }

        public function reflectionHistory(string $actorName, int $beforeEventId): array { return []; }

        public function beginForListener(int $listenerId): bool
        {
            $this->locks[] = $listenerId;
            $this->transactionRows = unserialize(serialize($this->npcs), ['allowed_classes' => true]);
            return true;
        }

        public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets, array $relationshipKeysToRemove = []): bool
        {
            $this->writes[] = $npcId;
            if (!isset($this->npcs[$npcId])) {
                return false;
            }
            $row = $this->npcs[$npcId];
            $extended = clone $row['extended_data'];
            $relationships = $extended->relationships ?? new \stdClass();
            if (is_array($relationships)) {
                $relationships = (object)$relationships;
            }
            foreach ($relationshipKeysToRemove as $key) {
                unset($relationships->{$key});
            }
            foreach ($relationshipEdges as $key => $edge) {
                $relationships->{$key} = $edge;
            }
            if ($relationshipEdges !== [] || $relationshipKeysToRemove !== []) {
                $extended->relationships = $relationships;
            }
            $plugins = clone $row['plugin_extended_data'];
            $plugins->mind_poisoning = $mindPoisoningData;
            $row['extended_data'] = $extended;
            $row['plugin_extended_data'] = $plugins;
            $row['gamets_last_updated'] = $gamets;
            $this->npcs[$npcId] = $row;
            return true;
        }

        public function backupAndVerify(int $npcId, array $expected): bool
        {
            $this->snapshots++;
            $row = $this->npcs[$npcId] ?? null;
            $verified = is_array($row)
                && sameJsonValue($row['extended_data'] ?? null, $expected['extended_data'] ?? null)
                && sameJsonValue($row['plugin_extended_data'] ?? null, $expected['plugin_extended_data'] ?? null)
                && (float)($row['gamets_last_updated'] ?? -1) === (float)($expected['gamets_last_updated'] ?? -2);
            if ($verified && $this->afterSnapshot !== null) {
                ($this->afterSnapshot)($this);
            }
            return $verified;
        }

        public function commit(): bool
        {
            $this->commits++;
            return $this->commitSucceeds;
        }

        public function rollback(): void
        {
            $this->rollbacks++;
            if ($this->transactionRows !== []) {
                $this->npcs = $this->transactionRows;
            }
        }

        public function release(): void {}

        public function restoreEventBounds(): ?array
        {
            $this->boundsReads++;
            return ['latest_init_event_id' => $this->latestInitEventId, 'max_event_id' => $this->maxEventId];
        }

        public function acquireRestorePolicyBarrier(): bool { return true; }
        public function releaseRestorePolicyBarrier(): void {}

        public function writtenEvidenceById(int $eventId): ?array
        {
            $this->sourceReads++;
            return $this->events[$eventId] ?? null;
        }

        public function writtenEvidenceIdentityEvents(
            string $documentId,
            string $playthroughId,
            int $recipientNpcId,
            string $receiptId,
            string $operationId,
            int $afterEventIdExclusive,
            int $throughEventIdInclusive
        ): ?array {
            $this->identityReads++;
            $matches = [];
            foreach ($this->events as $event) {
                $id = $event['event_id'] ?? null;
                if (!is_int($id) || $id <= $afterEventIdExclusive || $id > $throughEventIdInclusive) {
                    continue;
                }
                $envelope = parseWrittenEvidenceEnvelope($event['source_data'] ?? '');
                if (!is_array($envelope)) {
                    continue;
                }
                if (($envelope['document_id'] ?? null) !== $documentId
                    && ($envelope['receipt']['receipt_id'] ?? null) !== $receiptId
                    && ($envelope['receipt']['operation_id'] ?? null) !== $operationId) {
                    continue;
                }
                $normalized = normalizeWrittenEvidenceSourceRow($event);
                if (!is_array($normalized)) {
                    return null;
                }
                $matches[] = $normalized + [
                    'document_id' => $envelope['document_id'],
                    'receipt_id' => $envelope['receipt']['receipt_id'],
                    'operation_id' => $envelope['receipt']['operation_id'],
                    'playthrough_id' => $envelope['playthrough_id'],
                    'recipient_id' => $envelope['recipient']['id'],
                    'recipient_matches' => $envelope['recipient']['id'] === $recipientNpcId,
                ];
            }
            usort($matches, static fn(array $a, array $b): int => $a['event_id'] <=> $b['event_id']);
            return array_slice($matches, 0, 2);
        }
    }

    function writtenCheck(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function writtenClone(mixed $value): mixed
    {
        return unserialize(serialize($value), ['allowed_classes' => true]);
    }

    function writtenNpc(int $id, string $name): array
    {
        return [
            'id' => $id,
            'npc_name' => $name,
            'personality' => 'Practical and generous.',
            'extended_data' => (object)['relationships' => new \stdClass()],
            'plugin_extended_data' => new \stdClass(),
            'gamets_last_updated' => 0.0,
            'lock_profile' => 0,
        ];
    }

    function writtenSource(int $eventId, string $documentId, string $receiptId, string $operationId, string $body): array
    {
        $envelope = [
            'schema_version' => 1,
            'document_id' => $documentId,
            'playthrough_id' => 'unprofiled',
            'recipient' => ['kind' => 'npc', 'id' => 900001, 'name' => 'Camilla'],
            'claimed_sender' => ['kind' => 'npc', 'id' => 900002, 'name' => 'Jarl Balgruuf'],
            'body' => $body,
            'receipt' => [
                'receipt_id' => $receiptId,
                'operation_id' => $operationId,
                'status' => 'confirmed',
                'exposure' => 'context',
            ],
            'attachments' => [['kind' => 'gold', 'form_id' => 'Skyrim.esm|0000000F', 'quantity' => 50]],
        ];
        return [
            'event_id' => $eventId,
            'source_type' => 'info',
            'source_sess' => 'written_evidence_v1',
            'source_ts' => '1730000000000',
            'source_gamets' => '14.25',
            'gamets' => 14.25,
            'source_localts' => 1730000001,
            'source_people' => '|Camilla|',
            'source_data' => json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ];
    }

    function writtenResponse(array $messages, bool $zero = false): string
    {
        $payload = json_decode($messages[1]['content'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        $subjects = $payload['untrusted_data']['candidates'] ?? null;
        if (!is_array($subjects)) {
            throw new \RuntimeException('Written prompt omitted its explicit candidate map.');
        }
        $judgments = [];
        foreach ($subjects as $token => $candidate) {
            if ($token === 'npc:900002') {
                $evidence = 'I enclose 50 septims.';
                $mentioned = true;
                $delta = $zero ? 0 : 1;
                $reason = 'The recipient appreciates the letter and gift.';
            } elseif ($token === 'npc:900003') {
                $evidence = 'Please hear Faendal fairly.';
                $mentioned = true;
                $delta = $zero ? 0 : -1;
                $reason = 'The recipient responds to the request.';
            } else {
                $evidence = 'Camilla, I enclose 50 septims.';
                $mentioned = false;
                $delta = 0;
                $reason = 'The letter provides no basis for a change.';
            }
            $judgments[] = [
                'subject' => $token,
                'subject_mentioned' => $mentioned,
                'delta' => $delta,
                'reason' => $reason,
                'evidence' => $evidence,
            ];
        }
        return json_encode(['judgments' => $judgments], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    function writtenAssertSourceOnly(array $event): void
    {
        foreach (['speaker_id', 'speaker_kind', 'listener_id', 'utterance_id', 'ack_identity'] as $forbidden) {
            writtenCheck(!array_key_exists($forbidden, $event), "No fabricated {$forbidden} field is allowed.");
        }
    }

    function writtenLogEvent(array $rows, string $event): ?array
    {
        foreach ($rows as $row) {
            if (($row['event'] ?? null) === $event) {
                return $row;
            }
        }
        return null;
    }

    $checks = 0;
    $same = static function (mixed $expected, mixed $actual, string $message) use (&$checks): void {
        $checks++;
        if ($expected !== $actual) {
            throw new \RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
        }
    };
    $check = static function (bool $condition, string $message) use (&$checks): void {
        $checks++;
        writtenCheck($condition, $message);
    };

    $capability = writtenEvidenceCapability();
    $same(1, $capability['version'] ?? null, 'Written API capability version must be stable.');
    $same('written_evidence', $capability['input_type'] ?? null, 'Capability must name only its source type.');
    $same('written_evidence_v1', $capability['source_marker'] ?? null, 'Capability must name the exact producer marker.');
    $same('info', $capability['source_event_type'] ?? null, 'Capability must identify the neutral physical carrier type.');
    $same(2000, $capability['max_body_codepoints'] ?? null, 'Capability must expose the frozen codepoint bound.');
    $same(8000, $capability['max_body_bytes'] ?? null, 'Capability must expose the frozen byte bound.');
    $same(true, $capability['recipient_only'] ?? null, 'Capability must state recipient-only ownership.');

    $baseBody = "Camilla, I enclose 50 septims. Please hear Faendal fairly.\nSigned, Jarl Balgruuf";
    $store = new WrittenEvidenceMemoryStore();
    $store->npcs = [
        900001 => writtenNpc(900001, 'Camilla'),
        900002 => writtenNpc(900002, 'Jarl Balgruuf'),
        900003 => writtenNpc(900003, 'Faendal'),
        900004 => writtenNpc(900004, 'TestHero'),
    ];
    $store->events[501] = writtenSource(501, '6c4dd05e-909c-4e93-8a11-7f72181a2df4', '914c2b35-7147-44f8-8c91-ae8745b11755', 'b98aec36-775d-4f41-aa71-90a753cff653', $baseBody);
    $store->events[502] = writtenSource(502, '1f20d092-6fcb-4cd9-b547-5b36dc60a9b0', '254ae584-3655-4637-9f10-f8e6e2cd8a0a', '3d2ca874-3788-4577-a421-bb736079074e', $baseBody);
    $store->events[503] = writtenSource(503, '2690a14f-4691-4edf-99ea-6bf1496f79c5', 'cc5cb10b-49af-4862-bd15-7f751e57e534', '3b323dec-2501-45a9-9924-0a86d230d39d', $baseBody);
    $store->events[504] = writtenSource(504, 'e1f0a078-ae9b-48fd-9308-eaa6c09656d5', '951205ad-72d6-423f-9cc0-d964c91a78df', 'd69e4ab4-1120-424e-b8f9-156a77ccb970', $baseBody);
    $store->events[505] = writtenSource(505, 'e1f0a078-ae9b-48fd-9308-eaa6c09656d5', '951205ad-72d6-423f-9cc0-d964c91a78df', 'd69e4ab4-1120-424e-b8f9-156a77ccb970', $baseBody);
    $store->events[506] = writtenSource(506, '49a4a124-21e2-4534-8d0e-66be8e8655ef', '866bbadb-6c3a-41ad-963c-cb7857bc41f3', 'bbd4962a-cddb-4a89-bf17-30ff19357eb5', $baseBody);
    $store->events[507] = writtenSource(507, '6f960fff-9174-47d9-97d8-b25e135a3b8f', 'ab99ed9a-f6f0-43d8-b011-edc92a3bc3df', 'cbbd85eb-62cb-4e8f-b95c-3c81c395f470', $baseBody);
    $store->events[508] = writtenSource(508, 'aa51d12d-f658-4b23-8935-9a5e9a5a0f8b', 'a3348b53-d62a-4de7-8082-6368fb03ce17', '3a588ef0-8196-49a1-a4a1-0885913a126b', $baseBody);
    $store->events[509] = writtenSource(509, '0ba16f7d-281e-4372-9728-1d6b1f9295a7', '568d6e45-1a5a-4a03-a34f-bab6892a6717', '338262f6-65e7-4ca7-ae44-93fa08d3bc1c', $baseBody);
    $store->events[510] = writtenSource(510, 'e2b89534-3384-4f0b-9b2b-c1b3f75de7c5', 'd7448a71-5dbd-4372-8c73-3944a897bb19', '71db0202-52bf-4c23-b4cc-c19e68716c10', $baseBody);
    $store->events[511] = writtenSource(511, '3888a587-719b-44bc-bd6a-cddfc79fe77d', '99361691-3374-4a80-83fd-fc3b6a969ed4', 'c3024a5d-b447-4a6b-928c-8bfac0554a32', $baseBody);
    $store->events[516] = writtenSource(516, 'c61fed28-22ee-49fb-8b82-76c2241923b2', '5dffaf56-3f5e-48ca-a2eb-4e3ee4c2dd8d', 'b3ec35fa-4a1d-47af-a0f4-c8f39b5e703f', $baseBody);
    $store->events[412] = writtenSource(412, 'b6813a7b-3e93-4fab-aa6a-54e81f671f34', '05b31e0e-08f7-49bc-a5e5-2e8f147d8ff2', '439b30fa-3775-4e5a-8c20-4a154dd00623', $baseBody);
    $store->events[413] = writtenSource(413, '98ea7a9a-81a6-4edc-9fa9-0c964d1ef2f2', '5f2d44ca-1cad-4b33-a2e9-d8f2178d7f99', '0f26b3d8-62e0-4bf3-9043-c28947dd2fc1', $baseBody);
    $store->events[414] = writtenSource(414, 'fbdd5ced-eac6-478b-bb74-d5a1b79486c3', '4c34b07a-1419-4379-9565-046e5e73f401', '7ac601eb-8e4a-47bb-9726-1f3e6b743054', 'A short private letter with no named candidate.');
    $selfAddressed = json_decode($store->events[414]['source_data'], true, 64, JSON_THROW_ON_ERROR);
    $selfAddressed['claimed_sender'] = ['kind' => 'npc', 'id' => 900001, 'name' => 'Camilla'];
    $store->events[414]['source_data'] = json_encode($selfAddressed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $store->events[415] = writtenSource(415, 'b07c37c0-9a98-4c5d-b0b2-b47cfa198879', 'c3963e29-1b5f-4791-811b-e6a00ac24df3', 'acc2be8e-601c-4d90-81b1-c2c42c7bd835', $baseBody);

    $parsed = parseWrittenEvidenceEnvelope($store->events[501]['source_data']);
    $check(is_array($parsed) && ($parsed['body'] ?? null) === $baseBody, 'The pure parser should return a valid frozen envelope.');
    $unknownField = json_decode($store->events[501]['source_data'], true, 64, JSON_THROW_ON_ERROR);
    $unknownField['unexpected'] = true;
    $check(parseWrittenEvidenceEnvelope(json_encode($unknownField, JSON_THROW_ON_ERROR)) === null, 'Unknown envelope fields must be rejected.');
    $badAttachment = $unknownField;
    unset($badAttachment['unexpected']);
    $badAttachment['attachments'][0]['quantity'] = 0;
    $check(parseWrittenEvidenceEnvelope(json_encode($badAttachment, JSON_THROW_ON_ERROR)) === null, 'Invalid confirmed attachment quantities must be rejected.');
    $caseVariantGold = $parsed;
    $caseVariantGold['attachments'][0]['kind'] = 'item';
    $caseVariantGold['attachments'][0]['form_id'] = 'skyrim.esm|0000000F';
    $check(parseWrittenEvidenceEnvelope(json_encode($caseVariantGold, JSON_THROW_ON_ERROR)) === null, 'Case-variant gold identities cannot bypass the canonical gold kind.');
    $check(parseWrittenEvidenceEnvelope('[]') === null, 'A JSON list cannot stand in for the envelope object.');

    $promptEvent = ['written_evidence' => $parsed, 'text' => $parsed['body'], 'player_name' => 'TestHero'];
    $promptCandidateSets = [
        'player-only' => ['player' => ['name' => 'Player', 'id' => null]],
        'npc-only' => ['npc:900002' => ['name' => 'Jarl Balgruuf', 'id' => 900002]],
        'mixed' => [
            'npc:900002' => ['name' => 'Jarl Balgruuf', 'id' => 900002],
            'player' => ['name' => 'Player', 'id' => null],
        ],
    ];
    foreach ($promptCandidateSets as $case => $candidates) {
        $prompt = buildWrittenEvidenceMessages($promptEvent, $store->npcs[900001], $candidates, null);
        $systemPrompt = $prompt[0]['content'] ?? '';
        $promptData = json_decode($prompt[1]['content'] ?? '', true, 64, JSON_THROW_ON_ERROR)['untrusted_data'] ?? [];
        $exampleSubject = json_encode(array_key_first($candidates), JSON_THROW_ON_ERROR);
        $check(str_contains($systemPrompt, 'Copy each `subject` value exactly from a key in the supplied `candidates` map'), "The {$case} prompt must require an exact supplied candidate key.");
        $check(str_contains($systemPrompt, 'Evidence must be a short, non-empty, non-whitespace exact excerpt from the original body for every judgment, including delta zero and `subject_mentioned` false'), "The {$case} prompt must require exact evidence for neutral and unmentioned judgments.");
        $check(str_contains($systemPrompt, 'A non-null `type_change` must be an object with exactly string fields `from` and `to`'), "The {$case} prompt must describe the exact type-change object schema.");
        $check(str_contains($systemPrompt, '`from` must match that candidate\'s supplied `current_type`, and `to` must be one of `allowed_relationship_types`'), "The {$case} prompt must bind type proposals to supplied type context.");
        $check(str_contains($systemPrompt, 'If either type context is missing, set `type_change` to null.'), "The {$case} prompt must tell the model to abstain when type context is missing.");
        $check(!array_key_exists('allowed_relationship_types', $promptData)
            && !array_key_exists('current_type', $promptData['candidates'][array_key_first($candidates)] ?? []),
            "The {$case} no-type-context request must omit unavailable type fields.");
        $check(str_contains($systemPrompt, '"subject":' . $exampleSubject), "The {$case} prompt example must use an actual candidate key.");
        $check(!str_contains($systemPrompt, 'player or npc:<id>'), "The {$case} prompt must not teach a composite subject token.");
    }
    $compositeSubjectResponse = json_encode([
        'judgments' => [[
            'subject' => 'player or npc:930101',
            'subject_mentioned' => true,
            'delta' => 0,
            'reason' => 'brief',
            'evidence' => 'This is the exact letter excerpt.',
        ]],
    ], JSON_THROW_ON_ERROR);
    $compositeSubjectRejected = false;
    try {
        parseJudgments(
            $compositeSubjectResponse,
            ['npc:930101' => ['id' => 930101, 'name' => 'Candidate']],
            'This is the exact letter excerpt.'
        );
    } catch (JudgmentValidationFailure $error) {
        $compositeSubjectRejected = $error->reasonCode === 'judgment_subject_invalid';
    }
    $check($compositeSubjectRejected, 'The parser must still reject a composite token even when it embeds a valid candidate key.');
    $exactEvidence = 'The exact original body.';
    foreach (['', " \t\n"] as $blankEvidence) {
        $blankEvidenceResponse = json_encode([
            'judgments' => [[
                'subject' => 'npc:930101',
                'subject_mentioned' => true,
                'delta' => 0,
                'reason' => 'brief',
                'evidence' => $blankEvidence,
            ]],
        ], JSON_THROW_ON_ERROR);
        $blankEvidenceRejected = false;
        try {
            parseJudgments(
                $blankEvidenceResponse,
                ['npc:930101' => ['id' => 930101, 'name' => 'Candidate']],
                $exactEvidence
            );
        } catch (JudgmentValidationFailure $error) {
            $blankEvidenceRejected = $error->reasonCode === 'judgment_evidence_invalid';
        }
        $check($blankEvidenceRejected, 'Empty and whitespace evidence must stay invalid on a zero-delta judgment.');
    }
    $neutralWithExcerpt = json_encode([
        'judgments' => [[
            'subject' => 'npc:930101',
            'subject_mentioned' => false,
            'delta' => 0,
            'reason' => 'brief',
            'evidence' => $exactEvidence,
        ]],
    ], JSON_THROW_ON_ERROR);
    $neutralParsed = parseJudgments(
        $neutralWithExcerpt,
        ['npc:930101' => ['id' => 930101, 'name' => 'Candidate']],
        $exactEvidence
    );
    $check(($neutralParsed['npc:930101']['delta'] ?? null) === 0
        && ($neutralParsed['npc:930101']['evidence'] ?? null) === $exactEvidence,
        'A neutral unmentioned judgment remains valid when its evidence is an exact non-empty body excerpt.');

    $GLOBALS['written_connector_enabled'] = true;
    $GLOBALS['RELLLM_CONNECTOR'] = 1;
    $GLOBALS['chim_interaction_generation'] = 7;
    $GLOBALS['ENGINE_PATH'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-written-evidence-' . getmypid();
    if (!is_dir($GLOBALS['ENGINE_PATH']) && !mkdir($GLOBALS['ENGINE_PATH']) && !is_dir($GLOBALS['ENGINE_PATH'])) {
        throw new \RuntimeException('Could not create the synthetic pause-control root.');
    }
    file_put_contents($GLOBALS['ENGINE_PATH'] . DIRECTORY_SEPARATOR . 'main.php', "<?php\n");
    register_shutdown_function(static function (): void {
        $root = $GLOBALS['ENGINE_PATH'] ?? null;
        if (is_string($root) && is_file($root . DIRECTORY_SEPARATOR . 'main.php')) {
            @unlink($root . DIRECTORY_SEPARATOR . 'main.php');
        }
        if (is_string($root) && is_dir($root)) {
            @rmdir($root);
        }
    });

    $sourceReadsBefore = $store->sourceReads;
    $writesBefore = count($store->writes);
    $pending = writtenEvidenceStatus(501, $store);
    $same('pending', $pending['status'] ?? null, 'A fresh exact source without receipt should be pending.');
    $same('unchecked', $pending['credential_readiness'] ?? null, 'Status must not inspect credential readiness.');
    $check(!array_key_exists('_internal', $pending), 'Public status must not expose the body or internal owner context.');
    $same(0, count($store->writes) - $writesBefore, 'Read-only status must not baseline or write owner state.');
    $check($store->sourceReads > $sourceReadsBefore && $store->identityReads > 0, 'Status must freshly validate source and collision identity.');
    $same([], drainWrittenEvidenceHints(), 'An empty drain must return before constructing the native store.');

    if (($argv[1] ?? null) === '--source-status-only') {
        echo "written evidence source/status tests passed ({$checks} checks)\n";
        exit(0);
    }

    $queued = enqueueWrittenEvidence(501, $store);
    $same('pending', $queued['status'] ?? null, 'Enqueue registers a volatile hint without claiming durable work.');
    $same('written_hint_registered', $queued['reason'] ?? null, 'Enqueue must name its request-local-only behavior.');
    enqueueWrittenEvidence(502, $store);
    $check(($GLOBALS['MIND_POISONING_WRITTEN_EVIDENCE_HINTS'] ?? null) === [501, 502], 'Enqueue must save only validated event IDs in its bounded request-local list.');
    $same(0, count($store->writes) - $writesBefore, 'Enqueue must not write or baseline.');

    $modelCalls = 0;
    $model = static function (array $messages) use (&$modelCalls): string {
        $modelCalls++;
        $payload = json_decode($messages[1]['content'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        $data = $payload['untrusted_data'] ?? [];
        writtenCheck(isset($data['written_evidence']['body']), 'Prompt must preserve the original letter body.');
        writtenCheck(($data['recipient']['personality'] ?? null) === 'Practical and generous.', 'Prompt must retain the recipient’s existing bounded personality context.');
        writtenCheck(isset($data['written_evidence']['attachments'][0]), 'Prompt may use only confirmed attachment facts.');
        writtenCheck(!isset($data['speaker'], $data['listener'], $data['utterance']), 'Prompt must not manufacture spoken-event roles.');
        $serialized = json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        writtenCheck(str_contains($serialized, 'not proof of authorship'), 'Prompt must frame the claimed sender as uncertain.');
        writtenCheck(str_contains($serialized, 'payer is unknown'), 'Prompt must not attribute confirmed items to an unseen payer.');
        writtenCheck(!array_key_exists('npc:900001', $data['candidates'] ?? []), 'The recipient cannot become their own opinion candidate.');
        return writtenResponse($messages);
    };

    $assertPreflightEvent = static function (
        array $rows,
        string $outcome,
        string $reason,
        string $level,
        ?int $eventId,
        ?int $ownerId = null,
        ?string $playthroughId = null
    ) use ($same, $check, $baseBody): array {
        $same(1, count($rows), 'A public evaluator preflight return should emit exactly one diagnostic event.');
        $row = $rows[0] ?? [];
        $same('written_evidence_preflight_finished', $row['event'] ?? null, 'The diagnostic must use its fixed preflight event name.');
        $same(1, $row['schema_version'] ?? null, 'The diagnostic must preserve the existing log schema version.');
        $same('preflight', $row['stage'] ?? null, 'The diagnostic must identify the early preflight stage.');
        $same('written_evidence', $row['source_kind'] ?? null, 'The diagnostic must identify the logical source kind.');
        $same($outcome, $row['outcome'] ?? null, 'The diagnostic must preserve the public API outcome.');
        $same($reason, $row['reason'] ?? null, 'The diagnostic must preserve the public API reason.');
        $same($level, $row['level'] ?? null, 'The diagnostic severity must come from the fixed reason mapping.');
        $same('not_called', $row['model_outcome'] ?? null, 'Preflight must state that the model was not called.');
        $modelMs = $row['model_ms'] ?? null;
        $check((is_int($modelMs) || is_float($modelMs)) && is_finite((float)$modelMs) && (float)$modelMs === 0.0,
            'Preflight must report finite numeric zero model time.');
        $same('not_attempted', $row['persistence_outcome'] ?? null, 'Preflight must state that persistence was not attempted.');
        $same('not_attempted', $row['commit_state'] ?? null, 'Preflight must state that no commit was attempted.');
        $same(false, $row['committed'] ?? null, 'Preflight must never claim a committed opinion.');
        $elapsed = $row['elapsed_ms'] ?? null;
        $check((is_int($elapsed) || is_float($elapsed)) && is_finite((float)$elapsed)
            && $elapsed >= 0 && $elapsed <= 86_400_000, 'Preflight elapsed time must stay within the existing bounded numeric range.');
        if ($eventId === null) {
            $check(!array_key_exists('event_id', $row), 'An unverified event ID must not survive in the diagnostic.');
        } else {
            $same((string)$eventId, $row['event_id'] ?? null, 'The diagnostic may retain only the verified source event ID.');
        }
        if ($ownerId === null) {
            $check(!array_key_exists('opinion_owner_id', $row) && !array_key_exists('recipient_id', $row),
                'A preflight without verified recipient state must not claim an actor or owner.');
        } else {
            $same((string)$ownerId, $row['opinion_owner_id'] ?? null, 'The diagnostic owner must match the verified recipient.');
            $same((string)$ownerId, $row['recipient_id'] ?? null, 'The diagnostic recipient must match its owner.');
        }
        if ($playthroughId === null) {
            $check(!array_key_exists('playthrough_id', $row), 'An unverified scope must not survive in the diagnostic.');
        } else {
            $same($playthroughId, $row['playthrough_id'] ?? null, 'The diagnostic scope must match the verified source.');
        }
        foreach ([
            'speaker_id', 'speaker_kind', 'utterance_id', 'source_digest', 'model_reason',
        ] as $privateField) {
            $check(!array_key_exists($privateField, $row), "Preflight must not expose {$privateField}.");
        }
        foreach ([
            'changes', 'type_changes', 'type_decisions', 'arrangement_changes',
        ] as $changeField) {
            $check(!isset($row[$changeField]) || $row[$changeField] === [], "Preflight must not inherit applied {$changeField}.");
        }
        foreach ([
            'changed_count', 'affinity_changed_count', 'type_changed_count', 'arrangement_changed_count',
        ] as $countField) {
            $check(!isset($row[$countField]) || $row[$countField] === 0, "Preflight must not inherit a nonzero {$countField}.");
        }
        $encoded = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $check(!str_contains($encoded, $baseBody) && !str_contains(strtolower($encoded), 'credential'),
            'Preflight must not expose source body or credential text.');
        $check(writtenLogEvent($rows, 'request_finished') === null,
            'A preflight diagnostic must not be paired with an ordinary request terminal.');
        return $row;
    };

    $invalidRows = [];
    $invalidLog = new RequestLog(static function (string $line) use (&$invalidRows): void {
        $invalidRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
    }, false);
    $beforeInvalidModelCalls = $modelCalls;
    $beforeInvalidSourceReads = $store->sourceReads;
    $beforeInvalidWrites = count($store->writes);
    $beforeInvalidCommits = $store->commits;
    $beforeInvalidSnapshots = $store->snapshots;
    $invalidId = evaluateWrittenEvidence(0, $store, static function (): string {
        throw new \RuntimeException('Invalid IDs must stop before provider work.');
    }, $invalidLog);
    $same('invalid', $invalidId['status'] ?? null, 'An invalid public event ID must keep its existing API status.');
    $same('event_id_invalid', $invalidId['reason'] ?? null, 'An invalid public event ID must keep its existing API reason.');
    $same($beforeInvalidModelCalls, $modelCalls, 'An invalid public event ID must not call the model.');
    $same($beforeInvalidSourceReads, $store->sourceReads, 'An invalid public event ID must not read source data.');
    $same($beforeInvalidWrites, count($store->writes), 'An invalid public event ID must not write owner state.');
    $same($beforeInvalidCommits, $store->commits, 'An invalid public event ID must not start a commit.');
    $same($beforeInvalidSnapshots, $store->snapshots, 'An invalid public event ID must not create a snapshot.');
    $assertPreflightEvent($invalidRows, 'invalid', 'event_id_invalid', 'warning', null);

    $beforeDrainWrites = count($store->writes);
    $result = drainWrittenEvidenceHints($store, $model);
    $same('processed', $result['status'] ?? null, 'A valid model response and confirmed owner transaction should process the source.');
    $same(1, $modelCalls, 'One drain invocation may call the model only once.');
    $same([900001], array_values(array_unique($store->writes)), 'Only the addressed recipient may be written.');
    $check($store->commits === 1 && $store->snapshots === 1, 'A normal opinion must use one atomic owner snapshot.');
    $check(count($store->writes) > $beforeDrainWrites, 'The validated judgment must reach the production persistence writer.');
    $check(($store->npcs[900001]['extended_data']->relationships->{'Jarl Balgruuf'}->aff ?? null) === 1, 'The actual recipient edge should receive the accepted sender judgment.');
    $check(($store->npcs[900002]['plugin_extended_data']->mind_poisoning ?? null) === null, 'The claimed sender must not own or receive the recipient transaction.');
    $same([502], $GLOBALS['MIND_POISONING_WRITTEN_EVIDENCE_HINTS'] ?? null, 'A drain must leave later hints untouched for a later invocation.');

    $normalRows = [];
    $normalLog = new RequestLog(static function (string $line) use (&$normalRows): void {
        $normalRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
    }, false);
    $beforeFreshCalls = $modelCalls;
    $fresh = evaluateWrittenEvidence(516, $store, $model, $normalLog);
    $same('processed', $fresh['status'] ?? null, 'The ordinary fresh-source evaluator path must remain available.');
    $same('receipt_confirmed', $fresh['reason'] ?? null, 'The ordinary fresh-source API reason must remain unchanged.');
    $same($beforeFreshCalls + 1, $modelCalls, 'A fresh valid source must still call the model once.');
    $check(is_array(writtenLogEvent($normalRows, 'request_finished'))
        && writtenLogEvent($normalRows, 'written_evidence_preflight_finished') === null,
        'A normal evaluated source must retain its existing terminal event without a preflight event.');

    $receiptStatus = writtenEvidenceStatus(501, $store);
    $same('processed', $receiptStatus['status'] ?? null, 'Fresh source plus matching owner receipt should report processed.');
    $same('committed', $receiptStatus['outcome'] ?? null, 'A nonzero opinion result should preserve the committed receipt outcome.');
    $beforeDuplicateCalls = $modelCalls;
    $duplicate = enqueueWrittenEvidence(501, $store);
    $same('processed', $duplicate['status'] ?? null, 'A replay with a fresh matching receipt should be returned as processed.');
    $same($beforeDuplicateCalls, $modelCalls, 'A processed source must not call the model again.');

    $replayRows = [];
    $replaySinkCalls = 0;
    $replayObserverCalls = 0;
    $replayLog = new RequestLog(static function (string $line) use (&$replayRows, &$replaySinkCalls): void {
        $replaySinkCalls++;
        $replayRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
        throw new \RuntimeException('Synthetic sink failure must not change evaluator status.');
    }, false);
    $replayLog->observe(static function (array $record, string $level) use (&$replayObserverCalls): void {
        $replayObserverCalls++;
        throw new \RuntimeException('Synthetic observer failure must not change evaluator status.');
    });
    $replayLog->context([
        'source_kind' => 'intimacy',
        'event_id' => 999,
        'playthrough_id' => '998',
        'opinion_owner_id' => 900003,
        'recipient_id' => 900002,
        'speaker_id' => 900004,
        'speaker_kind' => 'npc',
        'utterance_id' => 'abcdef0123456789abcdef01',
        'model_outcome' => 'succeeded',
        'persistence_outcome' => 'committed',
        'persistence_reason' => 'receipt_confirmed',
        'commit_state' => 'confirmed',
        'committed' => true,
        'changed_count' => 1,
        'changes' => [['subject' => 'npc:900002', 'delta' => 1, 'before' => 0.0, 'after' => 1.0]],
        'type_changed_count' => 1,
        'type_changes' => [['subject' => 'npc:900002', 'before' => 'platonic', 'after' => 'friendly']],
        'type_decisions' => [['subject' => 'npc:900002', 'from' => 'platonic', 'to' => 'friendly', 'reason' => 'change_eligible']],
        'arrangement_changed_count' => 1,
        'arrangement_changes' => [[
            'subject' => 'npc:900002', 'basis' => 'direct_npc_dialogue', 'before' => null,
            'after' => 'casual', 'proposal' => 'casual', 'outcome' => 'applied',
        ]],
    ]);
    $beforeReplayCalls = $modelCalls;
    $beforeReplayWrites = count($store->writes);
    $beforeReplayCommits = $store->commits;
    $beforeReplaySnapshots = $store->snapshots;
    $expectedReplayApi = writtenEvidenceStatus(501, $store);
    $replay = evaluateWrittenEvidence(501, $store, static function (): string {
        throw new \RuntimeException('A processed source must not reach provider work.');
    }, $replayLog);
    $same($expectedReplayApi, $replay, 'Evaluator replay must preserve the entire existing public status result.');
    $check(!array_key_exists('_internal', $replay) && !array_key_exists('_preflight', $replay),
        'Evaluator-only validated context must not leak through the public result.');
    $same('processed', $replay['status'] ?? null, 'An evaluator replay must preserve its existing processed status.');
    $same('receipt_confirmed', $replay['reason'] ?? null, 'An evaluator replay must preserve its existing reason.');
    $same($beforeReplayCalls, $modelCalls, 'An evaluator replay must not call the model again.');
    $same($beforeReplayWrites, count($store->writes), 'An evaluator replay must not write owner state again.');
    $same($beforeReplayCommits, $store->commits, 'An evaluator replay must not commit again.');
    $same($beforeReplaySnapshots, $store->snapshots, 'An evaluator replay must not create another snapshot.');
    $same(1, $replaySinkCalls, 'A throwing sink should receive only the fixed preflight diagnostic.');
    $same(1, $replayObserverCalls, 'A throwing observer should receive only the fixed preflight diagnostic.');
    $replayDiagnostic = $assertPreflightEvent($replayRows, 'processed', 'receipt_confirmed', 'info', 501, 900001, 'unprofiled');
    $same('written_evidence_preflight_finished', $replayDiagnostic['event'] ?? null,
        'A replay must not emit an ordinary request terminal after its preflight event.');

    $zeroModel = static function (array $messages): string {
        $payload = json_decode($messages[1]['content'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        return writtenResponse($messages, true);
    };
    $zero = drainWrittenEvidenceHints($store, $zeroModel);
    $same('processed', $zero['status'] ?? null, 'A valid all-zero response still needs a durable receipt.');
    $zeroStatus = writtenEvidenceStatus(502, $store);
    $same('zero_change', $zeroStatus['outcome'] ?? null, 'An all-zero result must be distinguishable from a changed opinion.');

    $GLOBALS['MIND_POISONING_WRITTEN_EVIDENCE_HINTS'] = [];
    $beforeEmptyReads = $store->sourceReads;
    $same([], drainWrittenEvidenceHints($store, $model), 'An empty drain should return immediately.');
    $same($beforeEmptyReads, $store->sourceReads, 'An empty drain must not open the store or read a source.');

    $badResponse = static fn(array $messages): string => '{"judgments":[{"subject":"npc:900002","subject_mentioned":true,"delta":3,"reason":"not grounded","evidence":"not in the letter"}]}';
    enqueueWrittenEvidence(503, $store);
    $writesBeforeInvalid = count($store->writes);
    $invalid = drainWrittenEvidenceHints($store, $badResponse);
    $check(in_array($invalid['status'] ?? null, ['failed', 'invalid'], true), 'Invalid exact-body evidence must not be processed.');
    $same($writesBeforeInvalid, count($store->writes), 'Invalid model output must perform no owner write.');
    $same('pending', writtenEvidenceStatus(503, $store)['status'] ?? null, 'An uncommitted response must remain retryable, not permanently processed.');

    $beforeCollisionCalls = $modelCalls;
    $collision = writtenEvidenceStatus(504, $store);
    $check(in_array($collision['status'] ?? null, ['invalid', 'failed'], true), 'Duplicate source identities must fail closed.');
    $same($beforeCollisionCalls, $modelCalls, 'Source identity collisions must stop before provider work.');

    $GLOBALS['written_connector_enabled'] = false;
    $beforeDisabledCalls = $modelCalls;
    $disabled = evaluateWrittenEvidence(503, $store, $model);
    $check(in_array($disabled['status'] ?? null, ['paused', 'failed'], true), 'Disabled relationship evaluation must stop before model work.');
    $same($beforeDisabledCalls, $modelCalls, 'A disabled global connector gate must prevent model work.');

    $GLOBALS['written_connector_enabled'] = true;
    $GLOBALS['written_interaction_enabled'] = true;
    $beforeMidModelWrites = count($store->writes);
    $midModelCalls = 0;
    $midModel = static function (array $messages) use (&$midModelCalls): string {
        $midModelCalls++;
        $GLOBALS['written_interaction_enabled'] = false;
        return writtenResponse($messages);
    };
    $midModelDisabled = evaluateWrittenEvidence(503, $store, $midModel);
    $GLOBALS['written_interaction_enabled'] = true;
    $check($midModelCalls === 1, 'The interaction change must occur after the validated model call begins.');
    $check(in_array($midModelDisabled['status'] ?? null, ['paused', 'failed'], true), 'Disabling interaction during model work must not report processed.');
    $same($beforeMidModelWrites, count($store->writes), 'A mid-model interaction disable must prevent all owner writes.');
    $same('pending', writtenEvidenceStatus(503, $store)['status'] ?? null, 'A blocked mid-model result must remain retryable.');

    $beforeAliasWrites = count($store->writes);
    $aliasModel = static function (array $messages) use ($store): string {
        $store->npcs[900005] = writtenNpc(900005, 'Faendal Archer');
        return writtenResponse($messages);
    };
    $logRows = [];
    $requestLog = new RequestLog(static function (string $line) use (&$logRows): void {
        $logRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
    }, false);
    $aliasMutation = evaluateWrittenEvidence(
        eventId: 506,
        store: $store,
        requestModel: $aliasModel,
        requestLog: $requestLog
    );
    unset($store->npcs[900005]);
    $check(in_array($aliasMutation['status'] ?? null, ['failed', 'invalid'], true), 'A newly ambiguous short-name alias must invalidate the selected subject list.');
    $same($beforeAliasWrites, count($store->writes), 'A candidate ambiguity change during model work must perform no owner write.');
    $same('pending', writtenEvidenceStatus(506, $store)['status'] ?? null, 'An ambiguity-stale source must remain retryable.');
    $terminal = null;
    foreach ($logRows as $row) {
        if (($row['event'] ?? null) === 'request_finished') {
            $terminal = $row;
        }
    }
    $check(is_array($terminal) && ($terminal['source_kind'] ?? null) === 'written_evidence'
        && ($terminal['opinion_owner_id'] ?? null) === '900001', 'Terminal log must identify the logical source and recipient owner.');
    $encodedLogs = json_encode($logRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $check(!str_contains($encodedLogs, $baseBody) && !str_contains($encodedLogs, 'source_digest')
        && !str_contains($encodedLogs, 'speaker_id') && !str_contains($encodedLogs, 'utterance_id'), 'Logs must not disclose body, digest, or fabricated speaker/utterance identities.');

    $GLOBALS['MIND_POISONING_WRITTEN_EVIDENCE_HINTS'] = [];
    $same('pending', enqueueWrittenEvidence(414, $store)['status'] ?? null, 'A validated no-subject source should reach the request-local drain.');
    $beforeNoSubjectCalls = $modelCalls;
    $noSubject = drainWrittenEvidenceHints($store, static function () use (&$modelCalls): string {
        $modelCalls++;
        throw new \RuntimeException('No-subject sources must skip model work.');
    });
    $same('processed', $noSubject['status'] ?? null, 'A valid source with no opinion candidates should still be receipted.');
    $same($beforeNoSubjectCalls, $modelCalls, 'A no-subject source must not call the model.');
    $same('zero_change', writtenEvidenceStatus(414, $store)['outcome'] ?? null, 'A no-subject source should receive a truthful zero-change receipt.');

    $modelFailureRows = [];
    $modelFailureLog = new RequestLog(static function (string $line) use (&$modelFailureRows): void {
        $modelFailureRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
    }, false);
    $beforeModelFailureWrites = count($store->writes);
    $modelFailure = evaluateWrittenEvidence(507, $store, static function (array $messages): string {
        usleep(10000);
        throw new ModelRequestFailure('model_request_failed');
    }, $modelFailureLog);
    $same('failed', $modelFailure['status'] ?? null, 'A provider failure must remain a public failure.');
    $same('model_request_failed', $modelFailure['reason'] ?? null, 'The fixed model failure code must survive evaluation.');
    $same($beforeModelFailureWrites, count($store->writes), 'A model failure must not write recipient state.');
    $modelFailureTerminal = writtenLogEvent($modelFailureRows, 'request_finished');
    $same('model_request_failed', $modelFailureTerminal['reason'] ?? null, 'The terminal log must retain the fixed provider failure reason.');
    $check(($modelFailureTerminal['model_ms'] ?? 0) >= 5.0, 'A delayed model exception must retain measured model time.');
    $same('pending', writtenEvidenceStatus(507, $store)['status'] ?? null, 'A provider failure must leave the source retryable.');

    $validationRows = [];
    $validationLog = new RequestLog(static function (string $line) use (&$validationRows): void {
        $validationRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
    }, false);
    $validationResponse = static function (array $messages): string {
        $response = json_decode(writtenResponse($messages), true, 64, JSON_THROW_ON_ERROR);
        $response['judgments'][0]['evidence'] = 'not in the original letter';
        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    };
    $validationFailure = evaluateWrittenEvidence(503, $store, $validationResponse, $validationLog);
    $same('failed', $validationFailure['status'] ?? null, 'Invalid exact-body evidence must be a distinct failed evaluation.');
    $same('judgment_evidence_invalid', $validationFailure['reason'] ?? null, 'The fixed judgment validation reason must survive evaluation.');
    $validationTerminal = writtenLogEvent($validationRows, 'request_finished');
    $same('invalid', $validationTerminal['model_outcome'] ?? null, 'The terminal log must distinguish schema rejection from model failure.');
    $same('judgment_evidence_invalid', $validationTerminal['reason'] ?? null, 'The terminal log must preserve the validation reason.');

    $beforeCommitOwner = writtenClone($store->npcs[900001]);
    $beforeCommitWrites = count($store->writes);
    $store->commitSucceeds = false;
    $commitRows = [];
    $commitLog = new RequestLog(static function (string $line) use (&$commitRows): void {
        $commitRows[] = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
    }, false);
    $commitFailure = evaluateWrittenEvidence(508, $store, $model, $commitLog);
    $store->commitSucceeds = true;
    $same('failed', $commitFailure['status'] ?? null, 'An unconfirmed commit must not be reported as processed.');
    $same($beforeCommitWrites + 1, count($store->writes), 'The commit fault must occur after the owner write attempt.');
    $same(true, sameJsonValue($beforeCommitOwner['extended_data'], $store->npcs[900001]['extended_data']), 'A failed commit must roll back the opinion edge.');
    $same(true, sameJsonValue($beforeCommitOwner['plugin_extended_data'], $store->npcs[900001]['plugin_extended_data']), 'A failed commit must roll back its receipt.');
    $commitPersistence = writtenLogEvent($commitRows, 'persistence_finished');
    $same('unconfirmed', $commitPersistence['commit_state'] ?? null, 'Persistence logging must record an unconfirmed commit.');
    $same('commit-failed', $commitPersistence['persistence_reason'] ?? null, 'Persistence logging must keep the concrete fixed commit failure.');
    $same('error', $commitPersistence['level'] ?? null, 'An unconfirmed commit must retain error severity.');
    $commitTerminal = writtenLogEvent($commitRows, 'request_finished');
    $same('unconfirmed', $commitTerminal['commit_state'] ?? null, 'Terminal logging must not overwrite commit uncertainty.');
    $same('error', $commitTerminal['level'] ?? null, 'The terminal event must preserve unconfirmed-commit severity.');
    $same('pending', writtenEvidenceStatus(508, $store)['status'] ?? null, 'A failed commit must leave the source retryable.');

    $beforePruneWrites = count($store->writes);
    $pruned = evaluateWrittenEvidence(509, $store, static function (array $messages) use ($store): string {
        unset($store->events[509]);
        return writtenResponse($messages);
    });
    $check(in_array($pruned['status'] ?? null, ['failed', 'invalid'], true), 'A source pruned during model work must fail closed.');
    $same($beforePruneWrites, count($store->writes), 'A pruned source must not reach an owner write.');

    $beforeHashWrites = count($store->writes);
    $hashChanged = evaluateWrittenEvidence(510, $store, static function (array $messages) use ($store): string {
        $store->events[510]['source_data'] .= "\n";
        return writtenResponse($messages);
    });
    $check(in_array($hashChanged['status'] ?? null, ['failed', 'invalid'], true), 'A source byte change during model work must fail closed.');
    $same($beforeHashWrites, count($store->writes), 'A changed source digest must not reach an owner write.');

    $store->latestInitEventId = 0;
    $beforeInitWrites = count($store->writes);
    $initChanged = evaluateWrittenEvidence(511, $store, static function (array $messages) use ($store): string {
        $store->latestInitEventId = 10;
        return writtenResponse($messages);
    });
    $check(in_array($initChanged['status'] ?? null, ['failed', 'invalid'], true), 'An INIT change during model work must invalidate the captured fence.');
    $same($beforeInitWrites, count($store->writes), 'An INIT change must not reach an owner write.');

    $store->latestInitEventId = 450;
    $store->maxEventId = 900;
    $GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = false;
    $preInitAccepted = evaluateWrittenEvidence(412, $store, $model);
    $same('processed', $preInitAccepted['status'] ?? null, 'A surviving pre-INIT source remains eligible when preserve mode is disabled.');
    $GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = true;
    $beforeFencedCalls = $modelCalls;
    $fenced = evaluateWrittenEvidence(413, $store, $model);
    $same('invalid', $fenced['status'] ?? null, 'The same pre-INIT boundary must fence a source when preserve mode is enabled.');
    $same('source_before_init', $fenced['reason'] ?? null, 'The enabled restore fence must be explicit.');
    $same($beforeFencedCalls, $modelCalls, 'A pre-INIT source must be fenced before model work.');
    $GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = false;

    $beforeSnapshotOwner = writtenClone($store->npcs[900001]);
    $beforeSnapshotWrites = count($store->writes);
    $store->afterSnapshot = static function (): void {
        $GLOBALS['written_interaction_enabled'] = false;
    };
    $precommitOff = evaluateWrittenEvidence(415, $store, $model);
    $store->afterSnapshot = null;
    $GLOBALS['written_interaction_enabled'] = true;
    $check(in_array($precommitOff['status'] ?? null, ['failed', 'paused'], true), 'A gate disabled after snapshot must stop before commit.');
    $same($beforeSnapshotWrites + 1, count($store->writes), 'The precommit gate fault must occur after the staged owner write.');
    $same(true, sameJsonValue($beforeSnapshotOwner['extended_data'], $store->npcs[900001]['extended_data']), 'A precommit gate failure must roll back the staged opinion.');
    $same(true, sameJsonValue($beforeSnapshotOwner['plugin_extended_data'], $store->npcs[900001]['plugin_extended_data']), 'A precommit gate failure must roll back the staged receipt.');
    $same('pending', writtenEvidenceStatus(415, $store)['status'] ?? null, 'A precommit gate failure must leave the source retryable.');

    if (!class_exists(\RelationshipManager::class, false)) {
        require_once __DIR__ . '/fixtures/relationship_manager.cf5030f15781637498be86debe26fcf102f5690d.php';
    }
    $typedSubjects = ['npc:900002' => ['id' => 900002, 'name' => 'Jarl Balgruuf']];
    $typedCurrent = ['npc:900002' => 'platonic'];
    $typedPrompt = buildWrittenEvidenceMessages($promptEvent, $store->npcs[900001], $typedSubjects, $typedCurrent);
    $typedPayload = json_decode($typedPrompt[1]['content'], true, 64, JSON_THROW_ON_ERROR)['untrusted_data'] ?? [];
    $typedSystem = $typedPrompt[0]['content'] ?? '';
    $same('platonic', $typedPayload['candidates']['npc:900002']['current_type'] ?? null,
        'A typed written-evidence prompt must include the exact current type.');
    $same(\RelationshipManager::TYPES, $typedPayload['allowed_relationship_types'] ?? null,
        'Typed written-evidence targets must come from the pinned CHIM RelationshipManager allow-list.');
    $check(str_contains($typedSystem, '"type_change":null'),
        'The sample response must remain neutral rather than inventing a type transition.');
    $typedJudgment = static function (mixed $typeChange): array {
        return [
            'subject' => 'npc:900002',
            'subject_mentioned' => true,
            'delta' => -5,
            'reason' => 'brief',
            'evidence' => 'Signed, Jarl Balgruuf',
            'type_change' => $typeChange,
        ];
    };
    $typedBody = $parsed['body'];
    $stringTypeRejected = false;
    try {
        parseJudgments(
            json_encode(['judgments' => [$typedJudgment('betrayed')]], JSON_THROW_ON_ERROR),
            $typedSubjects,
            $typedBody,
            $typedCurrent
        );
    } catch (JudgmentValidationFailure $error) {
        $stringTypeRejected = $error->reasonCode === 'judgment_type_change_invalid';
    }
    $check($stringTypeRejected, 'A string type proposal must remain invalid; the parser must not coerce model output.');

    $typedParsed = parseJudgments(
        json_encode(['judgments' => [$typedJudgment(['from' => 'platonic', 'to' => 'betrayed'])]], JSON_THROW_ON_ERROR),
        $typedSubjects,
        $typedBody,
        $typedCurrent
    );
    $same(['from' => 'platonic', 'to' => 'betrayed'], $typedParsed['npc:900002']['type_change'] ?? null,
        'A well-formed built-in type proposal must pass the existing CHIM type parser.');

    $customCurrent = ['npc:900002' => 'moonbound'];
    $customPrompt = buildWrittenEvidenceMessages($promptEvent, $store->npcs[900001], $typedSubjects, $customCurrent);
    $customPayload = json_decode($customPrompt[1]['content'], true, 64, JSON_THROW_ON_ERROR)['untrusted_data'] ?? [];
    $check(($customPayload['candidates']['npc:900002']['current_type'] ?? null) === 'moonbound'
        && str_contains($customPrompt[0]['content'] ?? '', 'Preserve custom current types.'),
        'A custom current type must remain visible and explicitly preserved in the prompt.');
    $customParsed = parseJudgments(
        json_encode(['judgments' => [$typedJudgment(['from' => 'moonbound', 'to' => 'betrayed'])]], JSON_THROW_ON_ERROR),
        $typedSubjects,
        $typedBody,
        $customCurrent
    );
    $same('custom_type_preserved', relationshipTypePreservationReason(
        $customParsed['npc:900002']['type_change']['from'],
        $customParsed['npc:900002']['type_change']['to']
    ), 'A custom relationship type must retain the existing CHIM preservation policy.');

    echo "written evidence evaluator tests passed ({$checks} checks)\n";
}
