<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use RuntimeException;
use Throwable;

final class ModelRequestFailure extends RuntimeException
{
    private const REASONS = [
        'connector_id_invalid',
        'connector_unavailable',
        'connector_not_found',
        'connector_driver_unsupported',
        'connector_config_incomplete',
        'connector_api_key_missing',
        'model_response_empty',
        'model_response_invalid_type',
        'model_request_failed',
    ];

    public readonly string $reasonCode;

    public function __construct(string $reasonCode, ?Throwable $previous = null)
    {
        if (!in_array($reasonCode, self::REASONS, true)) {
            throw new \InvalidArgumentException('Unknown Mind Poisoning model failure reason.');
        }
        $this->reasonCode = $reasonCode;
        parent::__construct('Mind Poisoning model request failed.', 0, $previous);
    }
}

function positiveConnectorId(mixed $value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (!is_string($value) || preg_match('/\A[1-9][0-9]*\z/', $value) !== 1) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);
    return is_int($id) && $id > 0 ? $id : null;
}

function requestJudgments(array $messages, int $maxTokens = 1024): string
{
    if (!in_array($maxTokens, [1024, 4096], true)) {
        throw new \InvalidArgumentException('Invalid Mind Poisoning model token budget.');
    }
    $globalNames = [
        'CONNECTOR', 'HTTP_TIMEOUT', 'FORCE_MAX_TOKENS', 'DEBUG_DATA',
        'PATCH_PROMPT_ENFORCE_ACTIONS', 'COMMAND_PROMPT_ENFORCE_ACTIONS',
        'FUNC_LIST', 'responseTemplate', 'structuredOutputTemplate', 'grammar',
        'PROMPT_ACTIONS_LIST', 'CHIM_JSON_RESPONSE_EXT_LOADED',
        'FUNCTIONS_ARE_ENABLED', 'LLM_LANG',
    ];
    $saved = [];
    foreach ($globalNames as $name) {
        $saved[$name] = [array_key_exists($name, $GLOBALS), $GLOBALS[$name] ?? null];
    }

    try {
        $connectorId = positiveConnectorId($GLOBALS['RELLLM_CONNECTOR'] ?? null);
        if ($connectorId === null) {
            throw new ModelRequestFailure('connector_id_invalid');
        }

        if (!class_exists('\\LLMConnector', false)) {
            $enginePath = $GLOBALS['ENGINE_PATH'] ?? null;
            if (!is_string($enginePath) || trim($enginePath) === '') {
                throw new ModelRequestFailure('connector_unavailable');
            }
            $classFile = rtrim($enginePath, '/\\') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'llm_connector.class.php';
            if (!is_file($classFile)) {
                throw new ModelRequestFailure('connector_unavailable');
            }
            require_once $classFile;
        }
        if (!class_exists('\\LLMConnector', false)) {
            throw new ModelRequestFailure('connector_unavailable');
        }

        $llmConnector = new \LLMConnector();
        $row = $llmConnector->readOne($connectorId);
        if (!is_array($row) || positiveConnectorId($row['id'] ?? null) !== $connectorId) {
            throw new ModelRequestFailure('connector_not_found');
        }
        $driver = $row['driver'] ?? null;
        if (!is_string($driver) || !in_array($driver, ['openrouterjson', 'openrouterjsoncached'], true)) {
            throw new ModelRequestFailure('connector_driver_unsupported');
        }
        if (!is_string($row['model'] ?? null) || trim($row['model']) === '' || !is_string($row['url'] ?? null) || trim($row['url']) === '') {
            throw new ModelRequestFailure('connector_config_incomplete');
        }

        // setOldGlobals has no cached branch; seed the base config, then copy it for the cached driver.
        $baseRow = $row;
        $baseRow['driver'] = 'openrouterjson';
        $llmConnector->setOldGlobals($baseRow);
        $baseConfig = $GLOBALS['CONNECTOR']['openrouterjson'] ?? null;
        if (!is_array($baseConfig) || !is_string($baseConfig['API_KEY'] ?? null) || trim($baseConfig['API_KEY']) === '') {
            throw new ModelRequestFailure('connector_api_key_missing');
        }
        if ($driver === 'openrouterjsoncached') {
            $GLOBALS['CONNECTOR']['openrouterjsoncached'] = $baseConfig;
        }

        $connector = $llmConnector->getConnector($row);
        $GLOBALS['HTTP_TIMEOUT'] = 12;
        $GLOBALS['FORCE_MAX_TOKENS'] = $maxTokens;
        $response = $connector->fast_request($messages, ['MAX_TOKENS' => $maxTokens], 'mind_poisoning');
        if (!is_string($response)) {
            throw new ModelRequestFailure('model_response_invalid_type');
        }
        if (trim($response) === '') {
            throw new ModelRequestFailure('model_response_empty');
        }
        return $response;
    } catch (ModelRequestFailure $error) {
        throw $error;
    } catch (Throwable $error) {
        throw new ModelRequestFailure('model_request_failed', $error);
    } finally {
        foreach ($saved as $name => [$existed, $value]) {
            if ($existed) {
                $GLOBALS[$name] = $value;
            } else {
                unset($GLOBALS[$name]);
            }
        }
    }
}
