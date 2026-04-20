# AGENTS.md

## Setup

- Install project dependencies with `composer install` from the repository root.
- Install documentation generator dependencies with `composer install` from `build/`.

## Validation

- Run the test suite with `php vendor/bin/phpunit -c phpunit.xml`.

## Documentation

- `README_API.md` is generated. Do not edit it by hand.
- Regenerate it from the repository root with `php build/generate_docs.php`.
- The generator loads `build/docs/api.md`, scans `src/`, and documents `voku\helper\DomParserInterface`, `voku\helper\SimpleHtmlDomNodeInterface`, and `voku\helper\SimpleHtmlDomInterface`.

## Notes

- Keep changes focused and minimal.
- When updating public parser interfaces or documented API signatures, regenerate `README_API.md`.
