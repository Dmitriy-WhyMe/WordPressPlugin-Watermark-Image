@echo off
setlocal EnableExtensions

set "ROOT=%~dp0"
set "PLUGIN_SLUG=watermark-manager"
set "MAIN_FILE=watermark-manager.php"
set "DIST_DIR=%ROOT%dist"
set "BUILD_DIR=%ROOT%build"
set "STAGE_DIR=%BUILD_DIR%\%PLUGIN_SLUG%"
set "ZIP_PATH=%DIST_DIR%\%PLUGIN_SLUG%.zip"

cd /d "%ROOT%"

if not exist "%MAIN_FILE%" (
    echo Main plugin file not found: %MAIN_FILE%
    exit /b 1
)

if not exist "%DIST_DIR%" mkdir "%DIST_DIR%"
if not exist "%BUILD_DIR%" mkdir "%BUILD_DIR%"

if exist "%STAGE_DIR%" rmdir /s /q "%STAGE_DIR%"
mkdir "%STAGE_DIR%"

echo Preparing plugin files...
copy /y "%MAIN_FILE%" "%STAGE_DIR%\%MAIN_FILE%" >nul
if errorlevel 1 (
    echo Failed to copy %MAIN_FILE%
    exit /b 1
)

if exist "README.md" (
    copy /y "README.md" "%STAGE_DIR%\README.md" >nul
)

if exist "%ZIP_PATH%" del /f /q "%ZIP_PATH%"

echo Building ZIP archive...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Compress-Archive -Path '%STAGE_DIR%' -DestinationPath '%ZIP_PATH%' -Force"
if errorlevel 1 (
    echo ZIP build failed.
    exit /b 1
)

echo Cleaning temporary build files...
rmdir /s /q "%BUILD_DIR%"

echo.
echo Done. Archive created:
echo %ZIP_PATH%
echo.
echo Install in WordPress via: Plugins -^> Add New -^> Upload Plugin.

endlocal
