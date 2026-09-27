<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use RuntimeException;
use Throwable;

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

function requestJudgments(array $messages): string
{
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
            throw new RuntimeException('RELLLM_CONNECTOR must be a positive connector id.');
        }

        if (!class_exists('\\LLMConnector', false)) {
            $enginePath = $GLOBALS['ENGINE_PATH'] ?? null;
            if (!is_string($enginePath) || trim($enginePath) === '') {
                throw new RuntimeException('LLMConnector is unavailable.');
            }
            $classFile = rtrim($enginePath, '/\\') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'llm_connector.class.php';
            if (!is_file($classFile)) {
                throw new RuntimeException('LLMConnector is unavailable.');
            }
            require_once $classFile;
        }
        if (!class_exists('\\LLMConnector', false)) {
            throw new RuntimeException('LLMConnector is unavailable.');
        }

        $llmConnector = new \LLMConnector();
        $row = $llmConnector->readOne($connectorId);
        if (!is_array($row) || positiveConnectorId($row['id'] ?? null) !== $connectorId) {
            throw new RuntimeException('Configured LLM connector could not be loaded.');
        }
        $driver = $row['driver'] ?? null;
        if (!is_string($driver) || !in_array($driver, ['openrouterjson', 'openrouterjsoncached'], true)) {
            throw new RuntimeException('Configured LLM connector driver is unsupported.');
        }
        if (!is_string($row['model'] ?? null) || trim($row['model']) === '' || !is_string($row['url'] ?? null) || trim($row['url']) === '') {
            throw new RuntimeException('Configured OpenRouter connector is missing its model or URL.');
        }

        // setOldGlobals has no cached branch; seed the base config, then copy it for the cached driver.
        $baseRow = $row;
        $baseRow['driver'] = 'openrouterjson';
        $llmConnector->setOldGlobals($baseRow);
        $baseConfig = $GLOBALS['CONNECTOR']['openrouterjson'] ?? null;
        if (!is_array($baseConfig) || !is_string($baseConfig['API_KEY'] ?? null) || trim($baseConfig['API_KEY']) === '') {
            throw new RuntimeException('Configured OpenRouter connector has no API key.');
        }
        if ($driver === 'openrouterjsoncached') {
            $GLOBALS['CONNECTOR']['openrouterjsoncached'] = $baseConfig;
        }

        $connector = $llmConnector->getConnector($row);
        $GLOBALS['HTTP_TIMEOUT'] = 12;
        $GLOBALS['FORCE_MAX_TOKENS'] = 1024;
        $response = $connector->fast_request($messages, ['MAX_TOKENS' => 1024], 'mind_poisoning');
        if (!is_string($response) || trim($response) === '') {
            throw new RuntimeException('Configured LLM connector returned no response.');
        }
        return $response;
    } catch (RuntimeException $error) {
        throw $error;
    } catch (Throwable $error) {
        throw new RuntimeException('Judgment request failed.', 0, $error);
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
