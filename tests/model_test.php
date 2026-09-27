<?php
declare(strict_types=1);

function modelScopedGlobals(): array
{
    return [
        'CONNECTOR', 'HTTP_TIMEOUT', 'FORCE_MAX_TOKENS', 'DEBUG_DATA',
        'PATCH_PROMPT_ENFORCE_ACTIONS', 'COMMAND_PROMPT_ENFORCE_ACTIONS',
        'FUNC_LIST', 'responseTemplate', 'structuredOutputTemplate', 'grammar',
        'PROMPT_ACTIONS_LIST', 'CHIM_JSON_RESPONSE_EXT_LOADED',
        'FUNCTIONS_ARE_ENABLED', 'LLM_LANG',
    ];
}

final class FixtureFastRequestDriver
{
    public static array $calls = [];
    public static mixed $response = '{"judgments":[]}';
    public static bool $throws = false;
    public static ?Throwable $failure = null;

    public function __construct(private string $driver)
    {
    }

    public function fast_request(array $messages, array $params, string $purpose): mixed
    {
        self::$calls[] = [
            'driver' => $this->driver,
            'connector' => $GLOBALS['CONNECTOR'][$this->driver] ?? null,
            'messages' => $messages,
            'params' => $params,
            'purpose' => $purpose,
            'timeout' => $GLOBALS['HTTP_TIMEOUT'] ?? null,
            'forced_max_tokens' => $GLOBALS['FORCE_MAX_TOKENS'] ?? null,
        ];
        foreach (modelScopedGlobals() as $name) {
            $GLOBALS[$name] = 'fixture mutation: ' . $name;
        }
        if (self::$throws) {
            throw self::$failure ?? new RuntimeException('fixture provider failure');
        }
        return self::$response;
    }
}

final class LLMConnector
{
    public static array|false $row = false;
    public static array $readIds = [];
    public static array $setupRows = [];
    public static array $connectorRows = [];
    public static string $apiKey = 'fixture-key';

    public function readOne($id): array|false
    {
        self::$readIds[] = $id;
        return self::$row;
    }

    public function setOldGlobals(array $row): void
    {
        self::$setupRows[] = $row;
        $GLOBALS['CONNECTOR']['openrouterjson'] = [
            'url' => $row['url'] ?? '',
            'model' => $row['model'] ?? '',
            'PROVIDER' => $row['provider'] ?? '',
            'API_KEY' => self::$apiKey,
        ];
    }

    public function getConnector(array $row): FixtureFastRequestDriver
    {
        self::$connectorRows[] = $row;
        return new FixtureFastRequestDriver($row['driver']);
    }
}

require __DIR__ . '/../server/model.php';

use function ChimMindPoisoning\requestJudgments;

function modelCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function modelReason(callable $call, string $expected, string $message): void
{
    try {
        $call();
    } catch (RuntimeException $error) {
        modelCheck(($error->reasonCode ?? null) === $expected, $message . ' (reason was ' . ($error->reasonCode ?? 'missing') . ')');
        modelCheck($error->getMessage() === 'Mind Poisoning model request failed.', 'Model failure messages must not expose provider/configuration details.');
        return;
    }
    throw new RuntimeException($message . ' (no failure thrown)');
}

function seedScopedGlobals(): array
{
    foreach (modelScopedGlobals() as $index => $name) {
        if ($index % 2 === 0) {
            $GLOBALS[$name] = ['before' => $name];
        } else {
            unset($GLOBALS[$name]);
        }
    }
    $GLOBALS['CONNECTOR'] = ['existing' => ['model' => 'leave intact']];

    $snapshot = [];
    foreach (modelScopedGlobals() as $name) {
        $snapshot[$name] = [array_key_exists($name, $GLOBALS), $GLOBALS[$name] ?? null];
    }
    return $snapshot;
}

function checkScopedGlobals(array $expected): void
{
    foreach ($expected as $name => [$existed, $value]) {
        modelCheck(array_key_exists($name, $GLOBALS) === $existed, "Global $name presence was not restored.");
        if ($existed) {
            modelCheck($GLOBALS[$name] === $value, "Global $name value was not restored.");
        }
    }
}

function setFixtureRow(string $driver = 'openrouterjson'): void
{
    LLMConnector::$row = [
        'id' => '17',
        'driver' => $driver,
        'url' => 'https://fixture.invalid/api',
        'model' => 'fixture-model',
        'provider' => 'fixture',
    ];
}

$messages = [['role' => 'user', 'content' => 'fixture message']];
foreach (['openrouterjson', 'openrouterjsoncached'] as $driver) {
    $GLOBALS['RELLLM_CONNECTOR'] = '17';
    setFixtureRow($driver);
    $before = seedScopedGlobals();
    FixtureFastRequestDriver::$throws = false;
    FixtureFastRequestDriver::$response = '{"judgments":[]}';
    $callCount = count(FixtureFastRequestDriver::$calls);

    modelCheck(requestJudgments($messages) === FixtureFastRequestDriver::$response, "$driver response should be returned unchanged.");
    modelCheck(count(FixtureFastRequestDriver::$calls) === $callCount + 1, "$driver should issue exactly one fast_request.");
    $call = FixtureFastRequestDriver::$calls[array_key_last(FixtureFastRequestDriver::$calls)];
    modelCheck($call['driver'] === $driver, "Original $driver connector should be instantiated.");
    modelCheck(($call['connector']['model'] ?? null) === 'fixture-model', "$driver connector globals should be populated.");
    modelCheck($call['messages'] === $messages && $call['purpose'] === 'mind_poisoning', 'Messages and purpose should pass through unchanged.');
    modelCheck(($call['params']['MAX_TOKENS'] ?? null) === 1024, 'MAX_TOKENS must be fixed at 1024.');
    modelCheck($call['timeout'] === 12 && $call['forced_max_tokens'] === 1024, 'Fast request timeout and forced token cap must be set.');
    modelCheck(LLMConnector::$connectorRows[array_key_last(LLMConnector::$connectorRows)]['driver'] === $driver, 'getConnector must receive the validated original driver row.');
    modelCheck(LLMConnector::$readIds[array_key_last(LLMConnector::$readIds)] === 17, 'Canonical string connector ids should reach readOne as an integer.');
    modelCheck(LLMConnector::$setupRows[array_key_last(LLMConnector::$setupRows)]['driver'] === 'openrouterjson', 'Legacy global setup must use the supported base OpenRouter branch.');
    checkScopedGlobals($before);
}

foreach ([0, -2, '0', '017', '17x', null, true, 17.0] as $invalidId) {
    $GLOBALS['RELLLM_CONNECTOR'] = $invalidId;
    setFixtureRow();
    $before = seedScopedGlobals();
    modelReason(fn() => requestJudgments($messages), 'connector_id_invalid', 'Invalid configured connector id should have a stable reason.');
    checkScopedGlobals($before);
}

$GLOBALS['RELLLM_CONNECTOR'] = 17;
LLMConnector::$row = false;
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'connector_not_found', 'Missing connector rows should have a stable reason.');
checkScopedGlobals($before);

setFixtureRow('unsupported');
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'connector_driver_unsupported', 'Unsupported drivers should have a stable reason.');
checkScopedGlobals($before);

setFixtureRow();
LLMConnector::$row['model'] = '';
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'connector_config_incomplete', 'Missing model configuration should have a stable reason.');
checkScopedGlobals($before);

setFixtureRow();
LLMConnector::$row['url'] = '';
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'connector_config_incomplete', 'Missing URL configuration should have a stable reason.');
checkScopedGlobals($before);

setFixtureRow();
LLMConnector::$row['id'] = '18';
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'connector_not_found', 'A mismatched row id should have a stable reason.');
checkScopedGlobals($before);

setFixtureRow();
LLMConnector::$apiKey = '';
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'connector_api_key_missing', 'A missing API key should have a stable reason.');
checkScopedGlobals($before);
LLMConnector::$apiKey = 'fixture-key';

foreach (['openrouterjson', 'openrouterjsoncached'] as $driver) {
    setFixtureRow($driver);
    $before = seedScopedGlobals();
    FixtureFastRequestDriver::$throws = true;
    FixtureFastRequestDriver::$failure = new RuntimeException('Configured OpenRouter connector has no API key.', 151);
    modelReason(fn() => requestJudgments($messages), 'model_request_failed', "$driver exceptions should remain generic even when their text mimics a known setup failure.");
    checkScopedGlobals($before);
    FixtureFastRequestDriver::$throws = false;
    FixtureFastRequestDriver::$failure = null;
}

setFixtureRow('openrouterjsoncached');
$before = seedScopedGlobals();
FixtureFastRequestDriver::$response = '';
modelReason(fn() => requestJudgments($messages), 'model_response_empty', 'Empty provider responses should have a stable reason.');
checkScopedGlobals($before);
FixtureFastRequestDriver::$response = false;
$before = seedScopedGlobals();
modelReason(fn() => requestJudgments($messages), 'model_response_invalid_type', 'Non-string provider responses should have a stable reason.');
checkScopedGlobals($before);

$unavailableSource = '$GLOBALS["RELLLM_CONNECTOR"] = 17; $GLOBALS["ENGINE_PATH"] = ""; require '
    . var_export(__DIR__ . '/../server/model.php', true)
    . '; try { ChimMindPoisoning\\requestJudgments([]); } catch (RuntimeException $error) { echo $error->reasonCode ?? "missing"; exit(0); } exit(2);';
$unavailableProcess = proc_open([PHP_BINARY, '-r', $unavailableSource], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $unavailablePipes);
modelCheck(is_resource($unavailableProcess), 'The unavailable-connector subprocess should start.');
fclose($unavailablePipes[0]);
$unavailableOutput = stream_get_contents($unavailablePipes[1]);
fclose($unavailablePipes[1]);
$unavailableError = stream_get_contents($unavailablePipes[2]);
fclose($unavailablePipes[2]);
$unavailableExit = proc_close($unavailableProcess);
modelCheck($unavailableExit === 0, 'An unavailable connector should fail safely: ' . $unavailableError);
modelCheck($unavailableOutput === 'connector_unavailable', 'Missing connector runtime setup should have its stable reason.');

echo "model adapter checks passed\n";
