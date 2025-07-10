<?php
require_once 'vendor/autoload.php';
session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

try {
	$userPrompt = $_REQUEST['prompt'] ?? null;
	unset($_REQUEST['prompt']);

	$think = ($_REQUEST['think'] ?? null) == 'on' ?? $_SESSION['think'] ?? false;
	$_SESSION['think'] = $think;

	$messages = $_SESSION['messages'] ?? [];

	$lastMessage = null;
	if (!empty($userPrompt)) {
		$lastMessage = [
			"role" => "user",
			"content" => $userPrompt
		];
		$messages[] = $lastMessage;
	}

	$messages = run($messages, $think);
	$lastMessage = $messages[array_key_last($messages)];
	if ($lastMessage['role'] == 'assistant' && !empty($lastMessage['content'])) {
		$done = true;
	} else {
		$done = false;
	}

	$iteration = 0;
	while (!$done) {
		if ($iteration > 10) {
			header('Location: /');
			exit;
		}

		$messages = run($messages, $think);
		$lastMessage = $messages[array_key_last($messages)];
		if ($lastMessage['role'] == 'assistant' && !empty($lastMessage['content'])) {
			$done = true;
		}
	}

	$_SESSION['messages'] = $messages;

	header('Location: /');
	exit;
} catch (Throwable $e) {
	dd($e);
}

/**
 * @param array $messages
 * @param bool  $think
 *
 * @return array
 * @throws GuzzleException
 */
function run(array $messages = [], bool $think = false): array
{
	$response = chatCall($messages, $think);
	$result = json_decode($response->getBody(), true);
	$message = $result['message'];
	$messages[] = $message;

	$tools_calls = $message['tool_calls'] ?? [];

	if ($message['role'] == 'assistant' && !empty($tools_calls)) {
		$result = makeToolCall($tools_calls);
		array_push($messages, ...$result);
	}
	return $messages;
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

/**
 * @param array $tools_calls
 *
 * @return array
 *
 * @see man()
 * @see llmPrompt()
 * @see searchEngine()
 * @see lynx()
 */
function makeToolCall(array $tools_calls): array
{
	$log = $_SESSION['log'] ?? [];
	$to_return = [];

	foreach ($tools_calls as $tools_call) {
		$log[] = json_encode($tools_call);
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

	$_SESSION['log'] = $log;

	return $to_return;
}


/**
 * @param array $arguments
 *
 * @return false|string
 * @throws GuzzleException
 */
function searchEngine(array $arguments): false|string
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
	}
}

/**
 * `sudo apt install lynx`
 *
 * @param array $arguments
 *
 * @return false|string
 */
function lynx(array $arguments): false|string
{
	//lynx --accept_all_cookies --crawl --dump google
	$url = $arguments['url'];
	$arguments = $arguments['arguments'] ?? [];
	$command = "lynx ";
	foreach ($arguments as $argument) {
		$command .= "$argument ";
	}

	$command .= "$url";

	$output = null;
	exec($command, $output);

	return implode("\n", $output);
}

/**
 * @param array $arguments
 *
 * @return false|string
 */
function man(array $arguments): false|string
{
	//lynx --accept_all_cookies --crawl --dump google
	$command = $arguments['command'];
	$output = null;
	exec("man $command", $output);
	return $output;
}

/**
 * @param array $arguments
 *
 * @return false|string
 * @throws GuzzleException
 */
function llmPrompt(array $arguments): false|string
{
	$prompt = $arguments['prompt'];
	$client = new Client();
	$ollamaApiUrl = $_ENV['OLLAMA_BASE']; // Ollama endpoint
	$ollamaModel = $_ENV['LLM_MODEL']; // Your model name
	/** @var Response $response */
	$response = $client->post("$ollamaApiUrl/api/generate", [
		'json' => [
			'model' => $ollamaModel,
			"prompt" => $prompt,
			'stream' => false,
			'think' => false,
		]
	]);

	return $response->getBody()->getContents();
}

/**
 * @return array[]
 * @see llmPrompt()
 * @see searchEngine()
 * @see lynx()
 * @see man()
 */
function getTools(): array
{
	return [
		[
			'type' => 'function',
			'function' => [
				'name' => 'searchEngine',
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
		],
//		[
//			'type' => 'function',
//			'function' => [
//				'name' => 'llmPrompt',
//				'description' => 'Prompt an LLM without context or thinking. It uses the ollama /api/generate endpoint',
//				'parameters' => [
//					'type' => 'object',
//					'properties' => [
//						'prompt' => [
//							'type' => 'string',
//							'description' => 'The prompt to send to the LLM',
//						],
//					],
//					'required' => ['prompt'],
//				]
//			]
//		],
		[
			'type' => 'function',
			'function' => [
				'name' => 'lynx',
				'description' => 'lynx - a general purpose distributed information browser for the World Wide Web, for more arguments, check "man lynx"',
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'url' => [
							'type' => 'string',
							'description' => 'The URL to fetch the contents from',
						],
						'arguments' => [
							'type' => 'array',
							'description' => 'Extra arguments to pass to the lynx command-line tool.',
						],
					],
					'required' => ['url'],
				]
			]
		],
		[
			'type' => 'function',
			'function' => [
				'name' => 'man',
				'description' => "man  is the system's manual pager.  Each page argument given to man is normally the name of a program, utility or function.  The manual page associated with each of these
       arguments is then found and displayed.  A section, if provided, will direct man to look only in that section of the manual.  The default action is to search in all of the
       available sections following a pre-defined order (see DEFAULTS), and to show only the first page found, even if page exists in several sections.",
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'command' => [
							'type' => 'string',
							'description' => 'The command or program, utility or function to show manual from.',
						],
					],
					'required' => ['command'],
				]
			]
		],
	];
}
