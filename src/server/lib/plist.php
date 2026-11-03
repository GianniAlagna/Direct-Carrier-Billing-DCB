<?php
/**
 * QTPass - minimal XML property list writer.
 * iPhone OS 2.x parses plists natively (NSPropertyListSerialization),
 * so the API answers in plist instead of JSON (no JSON parser in the SDK).
 */

function qt_plist_value($v)
{
    if (is_bool($v)) {
        return $v ? '<true/>' : '<false/>';
    }
    if (is_int($v)) {
        return '<integer>' . $v . '</integer>';
    }
    if (is_array($v)) {
        $isList = array_keys($v) === range(0, count($v) - 1);
        if ($isList && count($v) > 0) {
            $out = '<array>';
            foreach ($v as $item) {
                $out .= qt_plist_value($item);
            }
            return $out . '</array>';
        }
        $out = '<dict>';
        foreach ($v as $k => $item) {
            $out .= '<key>' . htmlspecialchars($k) . '</key>' . qt_plist_value($item);
        }
        return $out . '</dict>';
    }
    return '<string>' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '</string>';
}

function qt_plist_out($data)
{
    header('Content-Type: application/x-plist; charset=utf-8');
    header('Cache-Control: no-store');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' . "\n";
    echo '<plist version="1.0">' . qt_plist_value($data) . '</plist>';
    exit;
}
