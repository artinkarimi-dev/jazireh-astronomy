@echo off
setlocal EnableExtensions DisableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

set "WORDPRESS_ROOT=C:\xampp\htdocs\wordpress"
set "PHP_EXE=C:\xampp\php\php.exe"
set "SYNC_SCRIPT=%SCRIPT_DIR%\sync.mjs"
set "SECRET_READER=%SCRIPT_DIR%\read-local-secret.php"
set "LOG_DIR=%SCRIPT_DIR%\logs"
set "LOCK_DIR=%SCRIPT_DIR%\run.lock"
set "CHANNEL_URL=https://www.youtube.com/@Jazireh/posts"
set "INGEST_URL=http://localhost/wordpress/wp-json/jazireh/v1/jazireh-daily-sync"
set "LOCAL_PROXY=http://127.0.0.1:10808"
set "NODE_EXE="
set "FAIL_REASON="
set "RUN_OUTCOME=FAILED"
set "RUN_YOUTUBE_STATUS="
set "RUN_WORDPRESS_STATUS="
set "RUN_CREATED="
set "RUN_UPDATED="
set "RUN_SKIPPED="
set "EXIT_CODE=1"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >nul 2>nul
call :timestamp RUN_STAMP
set "RUN_LOG=%LOG_DIR%\run-%RUN_STAMP%.log"
set "RUN_JSON=%LOG_DIR%\run-%RUN_STAMP%.json"
set "SECRET_FILE=%TEMP%\jazireh-daily-sync-secret-%RANDOM%-%RANDOM%.tmp"

2>nul mkdir "%LOCK_DIR%"
if errorlevel 1 (
  call :set_failure "Another local Community sync process is already running. Skipping this run."
  set "RUN_OUTCOME=SKIPPED"
  set "EXIT_CODE=0"
  goto :finalize
)

if not exist "%WORDPRESS_ROOT%\wp-load.php" (
  call :set_failure "WordPress bootstrap was not found at %WORDPRESS_ROOT%."
  goto :finalize
)

if not exist "%PHP_EXE%" (
  call :set_failure "PHP executable was not found at %PHP_EXE%."
  goto :finalize
)

call :resolve_node
if errorlevel 1 (
  call :set_failure "Node.js executable was not found. Install Node.js or set JAZIREH_NODE_EXE."
  goto :finalize
)

call :check_proxy
if errorlevel 1 (
  call :set_failure "Local v2rayN proxy 127.0.0.1:10808 is unavailable."
  goto :finalize
)

"%PHP_EXE%" "%SECRET_READER%" > "%SECRET_FILE%"
if errorlevel 1 (
  if exist "%SECRET_FILE%" del /q "%SECRET_FILE%" >nul 2>nul
  call :set_failure "Failed to read the Jazireh Daily sync secret from local WordPress."
  goto :finalize
)

set /p JAZIREH_DAILY_SYNC_SECRET=<"%SECRET_FILE%"
if exist "%SECRET_FILE%" del /q "%SECRET_FILE%" >nul 2>nul

if not defined JAZIREH_DAILY_SYNC_SECRET (
  call :set_failure "Failed to read the Jazireh Daily sync secret from local WordPress."
  goto :finalize
)

call :run_sync
if errorlevel 1 (
  >> "%RUN_LOG%" echo Retry scheduled in 30 seconds after transient failure.
  timeout /t 30 /nobreak >nul
  call :run_sync
)

set "EXIT_CODE=%ERRORLEVEL%"
if "%EXIT_CODE%"=="0" (
  set "RUN_OUTCOME=SUCCESS"
) else (
  if not defined FAIL_REASON call :capture_last_error
)

:finalize
call :summarize_run
call :rotate_logs
if defined JAZIREH_DAILY_SYNC_SECRET set "JAZIREH_DAILY_SYNC_SECRET="
if exist "%SECRET_FILE%" del /q "%SECRET_FILE%" >nul 2>nul
if exist "%LOCK_DIR%" rmdir "%LOCK_DIR%" >nul 2>nul
exit /b %EXIT_CODE%

:run_sync
if exist "%RUN_JSON%" del /q "%RUN_JSON%" >nul 2>nul
if exist "%RUN_LOG%" del /q "%RUN_LOG%" >nul 2>nul
pushd "%SCRIPT_DIR%" >nul
"%NODE_EXE%" "%SYNC_SCRIPT%" --channel-url="%CHANNEL_URL%" --ingest-url="%INGEST_URL%" --proxy="%LOCAL_PROXY%" 1>"%RUN_JSON%" 2>"%RUN_LOG%"
set "SYNC_EXIT=%ERRORLEVEL%"
popd >nul
exit /b %SYNC_EXIT%

:resolve_node
if defined JAZIREH_NODE_EXE if exist "%JAZIREH_NODE_EXE%" (
  set "NODE_EXE=%JAZIREH_NODE_EXE%"
  exit /b 0
)
if exist "%ProgramFiles%\nodejs\node.exe" (
  set "NODE_EXE=%ProgramFiles%\nodejs\node.exe"
  exit /b 0
)
for /f "delims=" %%I in ('where.exe node 2^>nul') do (
  if not defined NODE_EXE set "NODE_EXE=%%I"
)
if defined NODE_EXE exit /b 0
exit /b 1

:check_proxy
powershell -NoProfile -ExecutionPolicy Bypass -Command "$client = New-Object Net.Sockets.TcpClient; try { $iar = $client.BeginConnect('127.0.0.1', 10808, $null, $null); if (-not $iar.AsyncWaitHandle.WaitOne(3000, $false)) { exit 1 }; $client.EndConnect($iar) | Out-Null; exit 0 } catch { exit 1 } finally { $client.Close() }" >nul 2>nul
exit /b %ERRORLEVEL%

:set_failure
set "FAIL_REASON=%~1"
> "%RUN_LOG%" echo %~1
exit /b 0

:capture_last_error
for /f "usebackq delims=" %%I in (`powershell -NoProfile -ExecutionPolicy Bypass -Command "$lines = Get-Content -LiteralPath '%RUN_LOG%' -ErrorAction SilentlyContinue; if ($lines) { [Console]::Out.Write($lines[-1]) }"`) do set "FAIL_REASON=%%I"
if not defined FAIL_REASON set "FAIL_REASON=Sync failed without a detailed error message."
exit /b 0

:summarize_run
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$runStamp = '%RUN_STAMP%';" ^
  "$outcome = '%RUN_OUTCOME%';" ^
  "$logPath = '%RUN_LOG%';" ^
  "$jsonPath = '%RUN_JSON%';" ^
  "$historyPath = '%LOG_DIR%\history.log';" ^
  "$summary = [ordered]@{ youtube = ''; wordpress = ''; created = ''; updated = ''; skipped = ''; failure = '' };" ^
  "if (Test-Path -LiteralPath $logPath) {" ^
  "  $logText = Get-Content -LiteralPath $logPath -Raw;" ^
  "  $yt = [regex]::Match($logText, 'Community source request:\s*PROXY:\s*.*?\s*SOURCE:\s*.*?\s*STATUS:\s*(\d+)', 'Singleline');" ^
  "  if ($yt.Success) { $summary.youtube = $yt.Groups[1].Value }" ^
  "  $wp = [regex]::Match($logText, 'WordPress ingestion request:\s*PROXY:\s*.*?\s*SOURCE:\s*.*?\s*STATUS:\s*(\d+)', 'Singleline');" ^
  "  if ($wp.Success) { $summary.wordpress = $wp.Groups[1].Value }" ^
  "}" ^
  "if (Test-Path -LiteralPath $jsonPath) {" ^
  "  try { $payload = Get-Content -LiteralPath $jsonPath -Raw | ConvertFrom-Json; if ($payload.result -and $payload.result.data) { $summary.created = [string]$payload.result.data.created; $summary.updated = [string]$payload.result.data.updated; $summary.skipped = [string]$payload.result.data.skipped } } catch { }" ^
  "}" ^
  "$summary.failure = '%FAIL_REASON%';" ^
  "$historyLine = '{0}`t{1}`tYouTube={2}`tWordPress={3}`tcreated={4}`tupdated={5}`tskipped={6}`tfailure={7}' -f $runStamp, $outcome, $summary.youtube, $summary.wordpress, $summary.created, $summary.updated, $summary.skipped, $summary.failure;" ^
  "Add-Content -LiteralPath $historyPath -Value $historyLine;" ^
  "if (Test-Path -LiteralPath $logPath) { Set-Content -LiteralPath '%LOG_DIR%\latest.log' -Value (Get-Content -LiteralPath $logPath -Raw) } else { Set-Content -LiteralPath '%LOG_DIR%\latest.log' -Value $summary.failure };" ^
  "if (Test-Path -LiteralPath $jsonPath) { Copy-Item -LiteralPath $jsonPath -Destination '%LOG_DIR%\latest.json' -Force }"
exit /b 0

:rotate_logs
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$logDir = '%LOG_DIR%';" ^
  "Get-ChildItem -LiteralPath $logDir -File -Filter 'run-*' -ErrorAction SilentlyContinue | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-14) } | Remove-Item -Force -ErrorAction SilentlyContinue;" ^
  "$historyPath = Join-Path $logDir 'history.log';" ^
  "if (Test-Path -LiteralPath $historyPath) { $lines = Get-Content -LiteralPath $historyPath; if ($lines.Count -gt 1000) { $lines | Select-Object -Last 1000 | Set-Content -LiteralPath $historyPath } }"
exit /b 0

:timestamp
for /f "usebackq delims=" %%I in (`powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Date -Format 'yyyyMMdd-HHmmss'"`) do set "%~1=%%I"
exit /b 0
