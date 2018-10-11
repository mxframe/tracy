<?php

/**
 * Polyfill to make a function available within the tracy namespaces.
 */

namespace Tracy {

    function escapeshellarg($input): string
    {
        return \MxFrame\Tracy\escapeshellarg($input);
    }
}

namespace MxFrame\Tracy {

    if (function_exists('escapeshellarg') === true) {
        function escapeshellarg($input): string
        {
            return \escapeshellarg($input);
        }
    } else {
        function escapeshellarg($input): string
        {
            $input = str_replace('\'', '\\\'', $input);
            return '\'' . $input . '\'';
        }
    }
}
