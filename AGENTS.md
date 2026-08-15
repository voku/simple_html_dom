# AGENTS.md

## Setup

- Install project dependencies with `composer install` from the repository root.
- Install documentation generator dependencies with `composer install` from `build/`.

## Validation

- Run the test suite with `php vendor/bin/phpunit -c phpunit.xml`.

## Governed workflow (voku/agent-loop)

Non-trivial work in this repository runs through the `voku/agent-loop` workflow: board card ->
`workflow plan` -> `workflow approve` -> implement -> recorded validation -> review -> learning ->
`workflow close`.

- The CLI needs PHP >= 8.3 while this library supports PHP >= 7.1, so it is **not** a root
  dependency. It lives in the isolated Composer tool project `tools/agent-loop/`:

  ```shell
  composer install --working-dir=tools/agent-loop
  ```

- Run it through the repository wrapper, **not** through `vendor/bin/agent-loop` (which the
  installed skills name, but which does not exist here):

  ```shell
  php bin/agent-loop init status          # what is installed / what is missing
  php bin/agent-loop board summary        # the Kanban board
  php bin/agent-loop workflow status <id> # durable state of one task, before mutating anything
  ```

- Install or refresh the agent assets (skills, subagent roles, client hooks) with:

  ```shell
  php bin/agent-loop init install-assets --agent=all
  php bin/agent-loop init sync-hooks --agent=claude   # host-owned bundle, see below
  php bin/agent-loop init sync-hooks --agent=codex
  ```

  The generated `.claude/`, `.codex/`, `.agents/`, `.github/skills/` and `.github/agents/`
  projections are ignored by Git; the canonical sources are the Composer package and, for the
  hooks, `docs/agents/{claude,codex}-hooks/`. Those two bundles are host-owned copies of the
  package hooks: they resolve the agent-loop runtime from `tools/agent-loop/vendor/autoload.php`
  first and stay silent when it is missing, which the package-owned versions cannot do in this
  layout.

- Workflow state under `.agent-loop/` is committed for board, tasks, contracts, run receipts and
  learning findings. Navigation (`map/`), compiled `recall/` and `sessions/` are disposable and
  ignored.
- Findings about the workflow itself belong in `.agent-loop/learning/findings/validated/` plus a
  board card, not in this file.

## Documentation

- `README_API.md` is generated. Do not edit it by hand.
- Regenerate it from the repository root with `php build/generate_docs.php`.
- The generator loads `build/docs/api.md`, scans `src/`, and documents `voku\helper\DomParserInterface`, `voku\helper\SimpleHtmlDomNodeInterface`, and `voku\helper\SimpleHtmlDomInterface`.

## Notes

- Keep changes focused and minimal.
- When updating public parser interfaces or documented API signatures, regenerate `README_API.md`.
