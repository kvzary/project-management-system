<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class MentionParser
{
    /**
     * Token format: @[Full Name](user:123)
     */
    private const TOKEN_PATTERN = '/@\[([^\]]+)\]\(user:(\d+)\)/';

    /**
     * Extract mentioned users from a comment body.
     * Only returns users that actually exist in the DB (prevents IDOR).
     */
    public static function extract(string $body): Collection
    {
        preg_match_all(self::TOKEN_PATTERN, $body, $matches);

        if (empty($matches[2])) {
            return collect();
        }

        $ids = array_unique(array_map('intval', $matches[2]));

        return User::whereIn('id', $ids)->get();
    }

    /**
     * Render a comment body as a safe HtmlString.
     * Escapes all HTML, then replaces mention tokens with styled spans.
     * Returns HtmlString so Blade's {{ }} outputs it without double-escaping.
     *
     * With PREG_SPLIT_DELIM_CAPTURE the array layout is:
     * [plain, name, id, plain, name, id, ...]
     * because the pattern has 2 capture groups.
     */
    public static function render(string $body): HtmlString
    {
        $parts = preg_split(self::TOKEN_PATTERN, $body, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Guard against preg_split failure
        if ($parts === false) {
            return new HtmlString(nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')));
        }

        $result = '';
        $i = 0;

        while ($i < count($parts)) {
            // Plain text segment — escape HTML
            $result .= nl2br(htmlspecialchars($parts[$i], ENT_QUOTES, 'UTF-8'));
            $i++;

            // Next two segments are the capture groups: name, id
            if (isset($parts[$i], $parts[$i + 1])) {
                $safeName = htmlspecialchars($parts[$i], ENT_QUOTES, 'UTF-8');
                $result .= '<span class="mention">@' . $safeName . '</span>';
                $i += 2;
            }
        }

        return new HtmlString($result);
    }

    /**
     * Render a plain-text version for email (no HTML, tokens become @Name).
     */
    public static function plainText(string $body): string
    {
        return preg_replace(self::TOKEN_PATTERN, '@$1', $body) ?? $body;
    }
}
