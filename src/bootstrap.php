<?php

declare(strict_types=1);

/*
 * Process-wide timezone (ADR 0011 — docs/adr/0011-utc-storage-jst-display.md).
 *
 * NeNe Field stores every instant (created_at, updated_at, submitted_at,
 * approved_at, audit occurred_at, …) in **UTC** and converts to the operator's
 * locale only at the display edge (ADR 0012 / docs/development/i18n.md). Forcing
 * the process timezone to UTC makes every ambient date()/DateTimeImmutable produce
 * a UTC instant consistently across web, CLI, and test contexts, independent of the
 * host configuration (Tier A shared hosting / Tier B Docker).
 */
date_default_timezone_set('UTC');
