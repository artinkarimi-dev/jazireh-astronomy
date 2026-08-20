@echo off
cd /d "%~dp0..\frontend"
call npm install
if errorlevel 1 pause & exit /b 1
call npm run build:wordpress
if errorlevel 1 pause & exit /b 1
echo.
echo React build completed inside the WordPress theme.
pause
