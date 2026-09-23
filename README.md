# PHP AI Agent

An experimental PHP application that connects to a local Ollama-compatible chat endpoint and lets the model request tools while it works through a conversation.

## What it demonstrates

- Local-model chat through an Ollama-compatible API
- Structured tool definitions and tool-call handling
- Multi-step agent execution
- Optional model reasoning mode
- Search-engine integration
- Simple session-backed conversation history and execution logs

The current experiment exposes tools for web search, text-mode browsing with Lynx, and local manual-page lookup.

## Requirements

- PHP
- Composer
- A running Ollama-compatible chat endpoint
- A model that supports tool calling
- Optional search API credentials
- Lynx if the browsing tool is enabled

## Setup

```bash
composer install
cp .env.example .env
php -S 127.0.0.1:8000
```

Configure the model and endpoints in `.env`:

```dotenv
LLM_API_URL="http://localhost:11434/api/chat"
LLM_MODEL="qwen:latest"
SEARCH_API_URL=""
SEARCH_API_KEY=""
```

Then open `http://127.0.0.1:8000`.

## Execution flow

1. A user prompt is appended to the conversation.
2. The configured model receives the conversation and available tool schemas.
3. Requested tools are executed and their results are returned to the model.
4. The loop continues until the model produces a final response.

## Security status

This repository is an experiment, not a production-ready agent runtime. Some tools execute local commands using model-supplied arguments. Do not expose the application to untrusted users, prompts, or networks.

A production implementation should isolate tool execution, enforce strict argument schemas and allowlists, apply time and resource limits, restrict filesystem and network access, and record auditable execution results. Podman-based isolation is a suitable direction for that work.

## License

MIT
