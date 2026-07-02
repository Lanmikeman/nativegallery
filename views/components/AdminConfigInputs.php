<?php

use App\Services\KeyTranslation;

/** @param array<string, mixed> $data */
function renderConfigInputs(array $data, string $prefix = ''): void
{
    foreach ($data as $key => $value) {
        $name = $prefix !== '' ? "{$prefix}[{$key}]" : (string) $key;
        $label = KeyTranslation::key((string) $key);

        if (is_bool($value)) {
            $selected = $value ? 'true' : 'false';
            echo "<div class='mb-2'>
                    <label>{$label}</label>
                    <select class='form-control' name='{$name}'>
                        <option value='true' " . ($selected === 'true' ? 'selected' : '') . ">true</option>
                        <option value='false' " . ($selected === 'false' ? 'selected' : '') . ">false</option>
                    </select>
                  </div>";
        } elseif (is_string($value)) {
            echo "<div class='mb-2'>
                    <label>{$label}</label>
                    <input class='form-control' type='text' name='{$name}' value='" . htmlspecialchars($value, ENT_QUOTES) . "'>
                  </div>";
        } elseif (is_int($value) || is_float($value)) {
            echo "<div class='mb-2'>
                    <label>{$label}</label>
                    <input class='form-control' type='number' step='any' name='{$name}' value='{$value}'>
                  </div>";
        } elseif (is_array($value)) {
            if ($value === []) {
                echo "<div class='mb-2'>
                        <label>{$label}</label>
                        <input class='form-control' type='text' name='{$name}' value=''>
                      </div>";
                continue;
            }
            if (array_keys($value) === range(0, count($value) - 1)) {
                echo "<div class='mb-2'>
                        <label>{$label}</label>
                        <input class='form-control' type='text' name='{$name}' value='" . htmlspecialchars(implode(', ', array_map('strval', $value)), ENT_QUOTES) . "'>
                        <div class='form-text'>Список значений через запятую</div>
                      </div>";
                continue;
            }
            echo "<fieldset class='mb-3 p-2 border'><legend>{$label}</legend>";
            renderConfigInputs($value, $name);
            echo '</fieldset>';
        }
    }
}