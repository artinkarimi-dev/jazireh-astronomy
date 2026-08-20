@echo off
setlocal
schtasks /Run /TN "Jazireh Community Sync"
exit /b %ERRORLEVEL%
