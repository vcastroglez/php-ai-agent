<?php
require_once 'vendor/autoload.php';
session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use JetBrains\PhpStorm\NoReturn;

try {
	run();
} catch (Throwable $e) {
	dd($e);//vla
}

/**
 * @throws GuzzleException
 */
#[NoReturn] function run(): void
{
	$userPrompt = $_REQUEST['prompt'] ?? null;
	$think = ($_REQUEST['think'] ?? null) == 'on';

	if (empty($userPrompt)) {
		header('Location: /');
		exit;
	}
	$messages = $_SESSION['messages'] ?? [];
	$messages[] =
		[
			"role" => "user",
			"content" => $userPrompt
		];

	$response = chatCall($messages, $think);
	$result = json_decode($response->getBody(), true);
	$message = $result['message'];
	$messages[] = $message;

	$tools_calls = $message['tool_calls'] ?? [];

	if ($message['role'] == 'assistant' && !empty($tools_calls)) {
		$result = makeToolCall($tools_calls);
		array_push($messages, ...$result);
		$response = chatCall($messages, $think);
		$result = json_decode($response->getBody(), true);
		$message = $result['message'];
		$messages[] = $message;
	}

	$_SESSION['messages'] = $messages;
	header('Location: /');
	exit;
}

/**
 * @param array $messages
 * @param bool  $think
 *
 * @return Response
 * @throws GuzzleException
 */
function chatCall(array $messages, bool $think): Response
{
	$client = new Client();
	$ollamaApiUrl = $_ENV['LLM_API_URL']; // Ollama endpoint
	$ollamaModel = $_ENV['LLM_MODEL']; // Your model name
	/** @var Response $response */
	$response = $client->post($ollamaApiUrl, [
		'json' => [
			'model' => $ollamaModel,
			"messages" => $messages,
			'stream' => false,
			'think' => $think,
			'tools' => getTools(),
			'functions' => getTools(),
		]
	]);

	return $response;
}

function makeToolCall(array $tools_calls): array
{
	$to_return = [];

	foreach ($tools_calls as $tools_call) {
		foreach ($tools_call as $type => $details) {
			if ($type == 'function') {
				$name = $details['name'];
				$arguments = $details['arguments'] ?? [];
				$result = $name($arguments);
				$to_return[] = [
					'role' => 'tool',
					'content' => $result,
					'tool_name' => $name
				];
			}
		}
	}

	return $to_return;
}

function webQuery(array $arguments): false|string
{
	$query = $arguments['query'];
	$searchApiUrl = $_ENV['SEARCH_API_URL']; // Replace with your search URL, like serper.dev
	$searchApiKey = $_ENV['SEARCH_API_KEY']; // Replace with your key
	$client = new Client();
	try {
		$response = $client->post($searchApiUrl, ['headers' => ['X-API-KEY' => $searchApiKey, 'Content-Type' => 'application/json'], 'json' => ['q' => $query]]);
		return $response->getBody()->getContents();
	} catch (RequestException $e) { // Handle request exceptions (e.g., network issues, HTTP errors)
		return json_encode(['error' => 'Request failed: ' . $e->getMessage()]);
	} catch (GuzzleException $e) {
		return json_encode(['error' => 'Request failed: ' . $e->getMessage()]);
	}
}

function getTools(): array
{
	/** @see webQuery */
	return [
		[
			'type' => 'function',
			'function' => [
				'name' => 'webQuery',
				'description' => 'Query a search engine',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'query' => [
							'type' => 'string',
							'description' => 'The query to send to the search engine',
						],
					],
					'required' => ['query'],
				]
			]
		]
	];
}
