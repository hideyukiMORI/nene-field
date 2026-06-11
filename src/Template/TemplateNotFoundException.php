<?php

declare(strict_types=1);

namespace NeneField\Template;

use RuntimeException;

/**
 * The template does not exist in the caller's organization. A template in
 * another tenant surfaces as not found so existence is not disclosed across
 * organizations (problem type `template-not-found`, terms.md §7).
 */
final class TemplateNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The template was not found.');
    }
}
