<?php
// Run against the installed WordPress URL helpers without loading a site or database.
require __DIR__ . '/plugin-output.php';
function load_wsi_test_function($file, $name) {
    $tokens = token_get_all(file_get_contents($file));
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j = $i + 1;
        while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (!is_array($tokens[$j]) || $tokens[$j][1] !== $name) continue;
        $code = ''; $depth = 0; $started = false;
        for (; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $code .= is_array($token) ? $token[1] : $token;
            if ($token === '{') { $depth++; $started = true; }
            if ($token === '}' && --$depth === 0 && $started) { eval($code); return; }
        }
    }
    throw new RuntimeException('Missing function ' . $name);
}
function apply_filters($tag, $value, ...$args) { return $value; }
function home_url($path = '') { return 'https://example.test/sv' . $path; }
function get_current_user_id() { return 7; }
function get_user_meta($id, $key, $single = false) { return $GLOBALS['test_invite']; }
foreach (['build_query', '_http_build_query', 'add_query_arg'] as $name) {
    load_wsi_test_function(ABSPATH . 'wp-includes/functions.php', $name);
}
foreach (['urlencode_deep', 'map_deep', 'wp_parse_str'] as $name) {
    load_wsi_test_function(ABSPATH . 'wp-includes/formatting.php', $name);
}
foreach (['John Smith-abc123', 'Mary Jane Doe-xyz789', 'john+smith-code', 'john&smith-code', 'john%smith-code'] as $code) {
    $GLOBALS['test_invite'] = $code;
    $url = wsi_get_invite_link(7);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    if (preg_match('/\s/', $url) || $query['ref'] !== $code) throw new RuntimeException('Invite round trip failed');
}
foreach (["two words", "two\twords", "two\nwords", " leading", "trailing ", "two\u{00A0}words", "two\u{200B}words", "two\u{3000}words", ['bad']] as $name) {
    if (!wsi_username_has_whitespace($name)) throw new RuntimeException('Whitespace accepted');
}
foreach (['john', 'john.smith', 'john_smith', 'john-smith', 'john123'] as $name) {
    if (wsi_username_has_whitespace($name)) throw new RuntimeException('Valid username rejected');
}
echo "PASS: referral codes round trip through real WordPress URL helpers; username whitespace rejected.\n";
