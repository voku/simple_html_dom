# SHD-14: Bind StyleCI-only HTML5 compatibility test formatting

- **Ticket:** SHD-14
- **Lane:** READY
- **Status:** Selected
- **Domain:** tests
- **Created:** 2026-08-16T23:14:00+00:00
- **Updated:** 2026-08-16T23:14:00+00:00
- **Summary:** Apply the five StyleCI method-argument formatting changes in Html5DomParserCompatibilityTest.php and bind the resulting test-file bytes to a governed validation snapshot without changing assertions or parser behavior.
- **Format version:** 1

## Agent Task Brief
StyleCI identified only five `method_argument_space` fixes in `tests/Html5DomParserCompatibilityTest.php`: long `static::assertSame()` calls must put the expected argument on the following line. Make no assertion-value or runtime changes. Validate PHPUnit and PHPStan, run L2 blind-spot review, record no durable learning unless new evidence appears, and close on the exact formatted test snapshot.
