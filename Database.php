<?php

namespace FpDbTest;

use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        // Iterate through each argument and replace corresponding placeholders in the query
        foreach ($args as $arg) {
            // Check if the argument is a special value to be skipped
            if ($arg === $this->skip()) {
                // Skip this iteration
                continue;
            }

            // Determine the placeholder type and format the argument accordingly
            if (is_int($arg) || is_float($arg)) {
                $query = preg_replace('/\?f/', (float)$arg, $query, 1);
                $query = preg_replace('/\?d/', (int)$arg, $query, 1);
            } elseif (is_array($arg)) {
                // Check if the array is associative
                $isAssoc = array_keys($arg) !== range(0, count($arg) - 1);
                if ($isAssoc) {
                    // Convert associative array to pairs of identifiers and values
                    $pairs = [];
                    foreach ($arg as $key => $value) {
                        // Escape identifiers and values
                        $pairs[] = "`$key` = " . $this->escapeValue($value);
                    }
                    $query = preg_replace('/\?a/', implode(', ', $pairs), $query, 1);
                } else {
                    // Escape values in the array
                    $escapedValues = array_map(array($this, 'escapeValue'), $arg);
                    $query = preg_replace('/\?a/', implode(', ', $escapedValues), $query, 1);
                }
            } else {
                // Escape the value and replace the placeholder
                $query = preg_replace('/\?/', $this->escapeValue($arg), $query, 1);
            }
        }

        // Return the final query
        return $query;
    }

    public function skip()
    {
        // Return a special value to be skipped in the buildQuery method
        return '__SKIP__';
    }

    // Helper method to escape values
    private function escapeValue($value)
    {
        if ($value === null) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_string($value)) {
            return "'" . $this->mysqli->real_escape_string($value) . "'";
        } else {
            return $value; // No need to escape integers or floats
        }
    }
}

?>
