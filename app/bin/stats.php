#!/usr/bin/env php
<?php
require 'cli.php';

use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;
use App\Library\Helper;

$config = Helper::getConfig('beanstalk');
$beanstalk = new BeanstalkExtended([
    'host' => $config['host'],
    'prefix' => $config['prefix'],
]);

$statsData = [];
foreach ($beanstalk->getTubes() as $tube) {
    $statsData[$tube] = array_intersect_key($beanstalk->getTubeStats($tube), array_fill_keys(['total-jobs', 'current-jobs-ready', 'current-jobs-reserved', 'current-jobs-delayed', 'current-jobs-buried'], null));
}
echo (new Table())->render($statsData);


/**
 * Output table
 * Class Table
 */
class Table
{
    protected function bg($text, $status) {
        $out = "";
        switch($status) {
            case "SUCCESS":
                $out = "\033[42m"; //Green background
                break;
            case "FAILURE":
                $out = "\033[41m"; //Red background
                break;
            case "WARNING":
                $out = "\033[43m"; //Yellow background
                break;
            case "NOTE":
                $out = "\033[44m"; //Blue background
                break;
            default:
                throw new Exception("Invalid status: " . $status);
        }
        return "$out" . "$text" . "\033[0m";
    }

    protected function fg($text, $status) {
        $out = "";
        switch($status) {
            case "SUCCESS":
                $out = "\033[32m"; //Green foreground
                break;
            case "FAILURE":
                $out = "\033[31m"; //Red foreground
                break;
            case "WARNING":
                $out = "\033[33m"; //Yellow foreground
                break;
            case "NOTE":
                $out = "\033[34m"; //Blue foreground
                break;
            default:
                throw new Exception("Invalid status: " . $status);
        }
        return "$out" . "$text" . "\033[0m";
    }

    public function render(array $statsData)
    {
        $columns = array();
        $columnWidths = array();
        $output = "";
        foreach ($statsData as $data) {
            foreach ($data as $key => $val) {
                $columns[] = $key;
            }
        }
        $columns = array_unique($columns);
        //sort($columns);
        $rows = array_keys($statsData);
        $columnWidths["rows"] = $this->getMaxLength($rows);
        foreach ($columns as $column) {
            $tempValues = array();
            $tempValues[] = $column;
            foreach ($statsData as $tube => $data) {
                $tempValues[] = $data[$column];
            }
            $columnWidths[$column] = $this->getMaxLength($tempValues);
        }
        $dividerLine = str_repeat('-', array_sum($columnWidths) + 4 +  (3 * count($columns))) . "\n";
        $output .= $dividerLine;
        //output header row
        $output .= "| ";
        $cols[] = str_repeat(' ', $columnWidths["rows"]);
        foreach ($columns as $col) {
            $cols[] = str_pad($col, $columnWidths[$col], ' ');
        }
        $output .= implode(' | ', $cols);
        $output .= " |\n";
        $output .= $dividerLine;
        //output rows
        foreach ($statsData as $tube => $data) {
            $output .= "| ";
            $output .= $this->fg(str_pad($tube, $columnWidths["rows"], ' ', STR_PAD_LEFT), 'WARNING');
            $output .= " | ";
            $cols = array();
            foreach ($columns as $col) {
                $str = str_pad($data[$col], $columnWidths[$col], ' ');
                if ($col == 'current-jobs-buried' && $data[$col] != 0) {
                    $str = $this->fg($str, 'FAILURE');
                } else if ($data[$col] != 0) {
                    $str = $this->fg($str, 'SUCCESS');
                }
                $cols[] = $str;
            }
            $output .= implode(" | ", $cols);
            $output .= " |\n";
        }
        $output .= $dividerLine;
        return $output;
    }
    protected function getMaxLength($items)
    {
        $max = 0;
        foreach ($items as $item) {
            $length = strlen($item);
            if ($length > $max) {
                $max = $length;
            }
        }
        return $max;
    }
}