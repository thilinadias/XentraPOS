@echo off
:: XentraPOS Daily Summary Task
:: This script triggers the Daily Business summary email.
:: Best used with Windows Task Scheduler (e.g. 11:50 PM daily).

set PHP_BIN=C:\xampp\php\php.exe
set SCRIPT_PATH=C:\xampp\htdocs\pos\api\system\daily_cron.php

if exist %PHP_BIN% (
    %PHP_BIN% %SCRIPT_PATH%
    echo Daily Summary Dispatched.
) else (
    echo Error: PHP Executable not found at %PHP_BIN%
    pause
)
