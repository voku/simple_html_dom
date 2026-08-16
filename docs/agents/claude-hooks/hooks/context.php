<?php

declare(strict_types=1);

use voku\AgentLoop\AgentGuidance\AgentDisciplineHook;

$repositoryRoot = dirname(__DIR__, 2);

// Host-owned copy of the package hook.
//
// voku/agent-loop requires PHP >= 8.3 while this library still supports PHP >= 7.1,
// so the workflow CLI is installed as an isolated tool project under tools/agent-loop
// instead of the root Composer project. The package-owned hook resolves only the root
// vendor/autoload.php (and falls back to treating src/ as agent-loop's own source,
// which here is the parser library), so it can never load the hook runtime in this
// layout. Resolve the tool project first and stay non-blocking when it is absent.
foreach ([
    $repositoryRoot . '/tools/agent-loop/vendor/autoload.php',
    $repositoryRoot . '/vendor/autoload.php',
] as $autoloadCandidate) {
    if (is_file($autoloadCandidate)) {
        require $autoloadCandidate;
        break;
    }
}

if (!class_exists(AgentDisciplineHook::class)) {
    fwrite(
        STDERR,
        "agent-loop hook runtime is unavailable; run: composer install --working-dir=tools/agent-loop\n"
    );

    exit(0);
}

$event = 'SessionStart';
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--event=')) {
        $event = substr($argument, strlen('--event='));
    }
}

$rawPayload = stream_get_contents(STDIN, 1_048_577);
if (!is_string($rawPayload)) {
    fwrite(STDERR, "Unable to read hook payload.\n");
    exit(1);
}

try {
    echo json_encode(
        (new AgentDisciplineHook($repositoryRoot))->claudeContextOutput($event, $rawPayload),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
    ) . "\n";
} catch (Throwable $throwable) {
    fwrite(STDERR, 'agent-loop Claude context hook failed: ' . $throwable->getMessage() . "\n");
    exit(1);
}
