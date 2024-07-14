<?php

namespace FpDbTest;

include('DatabaseInterface.php');

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    private $specialMean = 'SKIP';

    public function __construct(mysqli $mysqli = null)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (strpos($query, "?") === false) {
            return $query;
        }

        $pattern = "/\?([d|f|a|#]*)/";

        preg_match_all($pattern, $query, $matches);

        for ($si = 0; $si < count($matches[0]); $si++) {
            $specifier = $matches[1][$si];

            $arg = $args[$si];

            $value = '';

            switch ($specifier) {
                case 'd':
                    $value = (int) $arg;
                    break;
                case 'f':
                    $value = (float) $arg;
                    break;
                case 'a':
                    $value = $this->arrayFormat($arg);
                    break;
                case '#':
                    $value = $this->idFormat($arg);
                    break;
                default:
                    $value = $this->escape($arg);
            }

            $query = preg_replace($pattern, $value, $query, 1);
        }

        $query = $this->conditionalBlocks($query, $arg);

        return $query;
    }

    public function skip(): string
    {
        return $this->specialMean;
    }

    private function escape($arg)
    {
        $value = '';

        switch ($arg) {
            case null:
                $value = 'NULL';
                break;
            case is_string($arg):
                $value = '\'' . $arg . '\'';
                // $value = $this->mysqli->real_escape_string($value);
                break;
            case is_int($arg):
                $value = (int) $arg;
                break;
            case is_float($arg):
                $value = (float) $arg;
                break;
            case is_bool($arg):
                $value = (int) $arg ? 1 : 0;
                break;
            case is_array($arg):
                $value = $this->arrayFormat($arg);
                break;
            default:
                throw new Exception('Unsupported parameter type');
        }

        return $value;
    }

    private function arrayFormat($arg)
    {
        if (is_array($arg)) {

            $formatted = [];

            foreach($arg as $k => $v) {
                if (is_int($k)) {
                    $formatted[] = $this->escape($v);
                } elseif (is_string($k)) {
                    $formatted[] = "`" . $k . "` = " . $this->escape($v);
                }
            }

            return implode(', ', $formatted);
        } else {
            return $this->escape($arg);
        }
    }

    private function idFormat($id)
    {
        if (is_array($id)) {
            return implode(', ', array_map(function($v) {
                return is_string($v) ? "`" . $v . "`" : $v;
            }, $id));
        } else {
            return "`" . $id . "`";
        }
    }

    private function conditionalBlocks($query, $arg) {
        if (preg_match('/\{(.*?)\}/', $query, $matches)) {
            if ($arg === $this->specialMean) {
                $query = preg_replace('/' . $matches[0] . '/', '', $query, 1);
            } else {
                $query = preg_replace('/' . $matches[0] . '/', $matches[1], $query, 1);
            }                
        }

        return $query;
    }
}