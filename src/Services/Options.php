<?php
namespace MTI\TypeformQuizzes\Services;

/**
 * Thin wrapper around the single array option used by the plugin.
 * Keeps the existing option name so no settings are lost.
 */
final class Options
{
    /** @var string */
    const OPTION_NAME = 'typeform_quizzes_defaults';

    /**
     * Get the entire settings array.
     *
     * @return array
     */
    public static function all(): array
    {
        $opts = get_option(self::OPTION_NAME, []);
        return is_array($opts) ? $opts : [];
    }

    /**
     * Persist the entire settings array.
     *
     * @param array $values
     * @return bool
     */
    public static function replace(array $values): bool
    {
        return update_option(self::OPTION_NAME, $values);
    }

    /**
     * Get a single key from the settings array.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $opts = self::all();
        return array_key_exists($key, $opts) ? $opts[$key] : $default;
    }

    /**
     * Update a single key in the settings array.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        $opts = self::all();
        $opts[$key] = $value;
        return self::replace($opts);
    }
}
