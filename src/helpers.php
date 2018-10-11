<?php

// @todombe: in eigenes project auslagern
if (! function_exists('getallheaders')) {
    /**
     * Get all HTTP header key/values as an associative array for the current request.
     *
     * @return array The HTTP header key/value pairs.
     */
    function getallheaders(): array
    {
        $headers = [];
        $copyServer = [
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        ];

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (! isset($copyServer[$key]) || ! isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copyServer[$key])) {
                $headers[$copyServer[$key]] = $value;
            }
        }

        if (! isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }
}

if (! function_exists('mx_get_client_ip')) {
    /**
     * Method to get the Client IP-Address.
     *
     * @param bool $anonymous       [optional] If true, the last block will be anonymous.
     * @param bool $acceptIpHeaders [optional] If false, (possible fakable) headers like HTTP_X_FORWARDED_FOR will be excluded.
     *
     * @return null|string The Client IP.
     */
    function mx_get_client_ip(bool $anonymous = false, bool $acceptIpHeaders = true): ?string
    {
        // Define the ip address
        $ipAddress = null;

        // First, check the possible fakable headers
        if (false !== $acceptIpHeaders) {
            // Define the keys
            $ipKeys = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
            ];

            // Try all the keys
            foreach ($ipKeys as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        // Trim for safety measures
                        $ip = trim($ip);
                        // Attempt to validate ip
                        if (true === mx_validate_ip($ip)) {
                            $ipAddress = $ip;
                            break;
                        }
                    }
                    if ($ipAddress) {
                        break;
                    }
                }
            }
        }

        // Use $_SERVER['REMOTE_ADDR'] as fallback
        if (null === $ipAddress) {
            $ipAddress = (empty($_SERVER) || empty($_SERVER['REMOTE_ADDR'])) ? false : $_SERVER['REMOTE_ADDR'];
        }

        // Check if the ip should be anonymous
        if (null !== $ipAddress && true === $anonymous) {
            // Get the glue and anonymous string
            if (false !== strpos($ipAddress, ':')) {
                $glue = ':';
                $anonymousString = '****';
            } elseif (false !== strpos($ipAddress, '.')) {
                $glue = '.';
                $anonymousString = '***';
            } else {
                $glue = '*';
                $anonymousString = '***';
            }

            // Remove the last set
            $string = explode($glue, $ipAddress);
            array_pop($string);
            $ipAddress = implode($glue, $string);

            // Add the anonymous positions
            $ipAddress .= $glue . $anonymousString;
        }

        // Return the result/ip
        return $ipAddress;
    }
}

if (! function_exists('mx_validate_ip')) {
    /**
     * Ensures an ip address is both a valid IP and does not fall within a private network range.
     *
     * @param string $ip The IP-Address.
     *
     * @return bool
     */
    function mx_validate_ip(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    }
}
