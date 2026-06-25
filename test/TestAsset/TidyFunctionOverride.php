<?php

declare(strict_types=1);

/**
 * Test-only override for the global tidy_clean_repair() function.
 *
 * The TidyMiddleware::process() method calls tidy_clean_repair() with an
 * unqualified name from inside the Ctw\Middleware\TidyMiddleware namespace.
 * PHP resolves such calls against the current namespace first and only falls
 * back to the global function when no namespaced function exists. By declaring
 * a function with the same name in that namespace here, the test suite can
 * deterministically force the repair step to report failure and exercise the
 * otherwise unreachable guard clause that returns the original response.
 *
 * When $GLOBALS['__ctw_force_repair_fail'] is falsy (the default for every
 * test) the override transparently delegates to the real global function, so
 * normal HTML processing is unaffected.
 */

namespace Ctw\Middleware\TidyMiddleware;

if (!\function_exists(__NAMESPACE__ . '\\tidy_clean_repair')) {
    /**
     * Delegate to the global tidy_clean_repair() unless a test has requested
     * a forced failure via the __ctw_force_repair_fail global flag.
     */
    function tidy_clean_repair(\tidy $tidy): bool
    {
        if (true === ($GLOBALS['__ctw_force_repair_fail'] ?? false)) {
            return false;
        }

        return \tidy_clean_repair($tidy);
    }
}
