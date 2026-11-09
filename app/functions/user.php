<?php

/**
 * Retorna o IP do usuário
 * @return string
 */
function getUserIP()
{

    $headers = [
        'MDH_USER_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $header) {
        $value = $_SERVER[$header] ?? getenv($header);
        if (!is_string($value) || trim($value) === '') {
            continue;
        }

        // Forwarded headers may contain multiple addresses and parameters.
        foreach (preg_split('/,/', $value) as $candidate) {
            $candidate = trim($candidate);
            if (preg_match('/(?:^|;)\s*for=("?)(\[[^\]]+\]|[^;\s,"]+)\1/i', $candidate, $match)) {
                $candidate = $match[2];
            }

            $candidate = trim($candidate, " \t\n\r\0\x0B\"'");
            if (isset($candidate[0]) && $candidate[0] === '[' && ($end = strpos($candidate, ']')) !== false) {
                $candidate = substr($candidate, 1, $end - 1);
            }

            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    return null;
}
    
