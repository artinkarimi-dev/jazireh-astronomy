@echo off
setlocal
schtasks /Delete /TN "Jazireh Community Sync" /F
exit /b %ERRORLEVEL%
