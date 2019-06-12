<?php

namespace Reaper;

class Logger
{
    public function log ($message, $level = 3)
    {
        $levels = [
            1 => 'Error',
            2 => 'Warning',
            3 => 'Info',
            4 => 'Debug',
        ];
        echo date('d-M-Y H:i:s') . ' - ' . $this->getCallingClass() . ' - ' .
            $levels[$level] . ' - ' . $message . "\n";
    }

    private function getCallingClass ()
    {
        $trace = debug_backtrace();
        $class = $trace[1]['class'];
        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i])) {
                if ($class != $trace[$i]['class']) {
                    return $trace[$i]['class'];
                }
            }
        }
    }
}