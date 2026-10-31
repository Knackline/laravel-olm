## knackline/laravel-olm

Unified Laravel client for local / on-device LLMs: `mlx`, `ollama`, `lmstudio`, `llamacpp`, `vllm`. Switch backends with config; same chat / stream / complete / embed API.

### Setup

@verbatim
<code-snippet name="Publish OLM config" lang="bash">
php artisan vendor:publish --provider="Knackline\Olm\Providers\OlmServiceProvider" --tag=olm-config
</code-snippet>
@endverbatim

Set `OLM_DRIVER` and per-driver `base_url` / `model` env vars. Entry points: `Knackline\Olm\Facades\Olm`, helper `olm()` / `olm('vllm')`, or inject `Knackline\Olm\OlmManager`.

@verbatim
<code-snippet name="Chat with default driver" lang="php">
use Knackline\Olm\Facades\Olm;

$response = Olm::chat([
    ['role' => 'user', 'content' => 'Hello!'],
], ['temperature' => 0.7]);

echo $response->content;
</code-snippet>
@endverbatim

`pull` / `show` are Ollama-only. For drivers, streaming, embeddings, pitfalls, and filing GitHub issues for missing features or bugs, activate the `laravel-olm` skill. When this package cannot satisfy a requested feature, bug, or support need, follow that skill’s issue-filing steps and open an issue on `Knackline/laravel-olm`.
