<?php
namespace Vanilla;

class EnvUtils
{
    /**
     * Simple function to extract contents of a .env file.
     * This is definitely not a full .env parser, but is enough to do some sanity checking.
     *
     * @param string $envContents The contens of a .env file.
     * @return array
     */
    public static function parseEnvFile(string $envContents): array
    {
        $envLines = explode("\n", $envContents);
        $envVariables = [];
        foreach ($envLines as $line) {
            $line = trim($line);
            // Whole line is a comment.
            if (empty($line) || str_starts_with($line, "#")) {
                continue;
            }

            // Line might end in a comment.
            $line = explode("#", $line, 2)[0];

            $parts = explode("=", $line, 2);
            // Now trim the parts
            $parts = array_map("trim", $parts);

            // If there is no second part (=) then treat it as an empty string
            if (count($parts) === 1) {
                $parts[] = "";
            }

            // The contents might be in quotes. If it is we need to trim the starting / end quote and decode any escaped quotes
            if (str_starts_with($parts[1], '"') && str_ends_with($parts[1], '"')) {
                $parts[1] = substr($parts[1], 1, -1);
                $parts[1] = str_replace('\"', '"', $parts[1]);
            }

            $envVariables[$parts[0]] = $parts[1];
        }
        return $envVariables;
    }

    /**
     * Given some existing contents of a .env file, update the contents with the new values.
     *
     * It preserves comments, blank lines, and the structure of existing variable assignments.
     * Variables in $newValues that match existing variables will replace the existing value.
     * Variables in $newValues that do not match existing variables will be appended to the end.
     *
     * @param string $existingContents The current content of the .env file.
     * @param array<string, string|int|bool> $newValues An associative array of key => value pairs to update or add. Values will be cast to string.
     *
     * @return string The updated .env file contents.
     */
    public static function updateEnvFileContents(string $existingContents, array $newValues): string
    {
        // Normalize line endings to LF for processing
        $existingContents = str_replace("\r\n", "\n", $existingContents);
        $lines = explode("\n", $existingContents);
        $outputLines = [];
        // Keep track of keys from $newValues that we haven't processed yet
        $keysToUpdate = $newValues;

        foreach ($lines as $line) {
            // Trim whitespace from the beginning to check for comments/emptiness easily
            $trimmedLine = ltrim($line);

            // Preserve comments and empty lines exactly as they are
            if (empty($trimmedLine) || $trimmedLine[0] === "#") {
                $outputLines[] = $line;
                continue;
            }

            // Check if the line contains an equals sign, indicating a potential variable assignment
            $equalPos = strpos($line, "=");

            // If no '=', it's not a standard key-value line we can update, so preserve it
            if ($equalPos === false) {
                $outputLines[] = $line;
                continue;
            }

            // Extract the part before '=', this includes potential 'export' and whitespace
            $keyPart = substr($line, 0, $equalPos);
            // Extract the actual key name by removing 'export' prefix (if any) and trimming whitespace
            // This regex matches optional whitespace, optional 'export', more optional whitespace,
            // and captures the variable name (letters, numbers, underscore, starting with letter/underscore)
            if (preg_match('/^\s*(?:export\s+)?([a-zA-Z_][a-zA-Z0-9_]*)\s*$/', $keyPart, $matches)) {
                $key = $matches[1];

                // Check if this extracted key exists in our new values array
                if (array_key_exists($key, $keysToUpdate)) {
                    // Construct the new line:
                    // Keep the original key part (e.g., "export MY_VAR " or "MY_VAR")
                    // Add the equals sign
                    // Add the new value (ensure it's a string)
                    $outputLines[] = rtrim($keyPart) . "=" . self::serializeEnvValue($keysToUpdate[$key]); // rtrim to remove trailing space before = if any, then add = and value

                    // Remove the key from our tracking array, as it has been updated
                    unset($keysToUpdate[$key]);
                } else {
                    // This key is not in our update list, so keep the original line
                    $outputLines[] = $line;
                }
            } else {
                // The line looked like an assignment but the key part didn't match expected format. Preserve it.
                $outputLines[] = $line;
            }
        }

        // Add any remaining keys from $newValues that were not found in the existing content
        // These are appended to the end of the file.
        if (!empty($keysToUpdate)) {
            // Add a blank line before appending new variables if the last line wasn't empty
            if (!empty($outputLines) && end($outputLines) !== "") {
                $outputLines[] = "";
            }
            foreach ($keysToUpdate as $key => $value) {
                // Ensure key format is valid before adding (optional, but good practice)
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                    $outputLines[] = $key . "=" . self::serializeEnvValue($value);
                } else {
                    throw new \Exception("Invalid environment variable name: $key");
                }
            }
        }

        // Join the lines back together using the standard Unix newline character
        return trim(implode("\n", $outputLines));
    }

    /**
     * Given a value, serialize it to a string for use in an env file.
     *
     * @param mixed $value
     * @return string
     */
    private static function serializeEnvValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(",", $value);
        } elseif (is_bool($value)) {
            return $value ? "true" : "false";
        } elseif (is_numeric($value)) {
            return (string) $value;
        } else {
            return (string) $value;
        }
    }
}
