# SHD-13: Bind PHP 8.3 static-analysis portability guards

- **Ticket:** SHD-13
- **Lane:** READY
- **Status:** Selected
- **Domain:** parser
- **Created:** 2026-08-16T23:08:00+00:00
- **Updated:** 2026-08-16T23:08:00+00:00
- **Summary:** Bind the annotation-only PHPStan guards for PHP 8.4-only Dom symbols to an exact governed implementation snapshot after normal CI exposed PHP 8.3 symbol-discovery differences.
- **Format version:** 1

## Agent Task Brief
The runtime/parser behavior was already validated and closed under SHD-11. Normal repository CI then revealed that PHPStan runs on PHP 8.3, where the guarded PHP 8.4-only `Dom` symbols are unknown. The final change only scopes PHPStan suppressions and corrects stale PHPDocs in `Html5DomParser.php`; it must not alter runtime behavior. Validate the full PHPUnit suite plus the repository PHPStan command on PHP 8.3, run L2 blind-spot review, record no durable learning unless new evidence appears, and close with an exact implementation snapshot.
