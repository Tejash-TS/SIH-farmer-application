@echo off
REM Start FastAPI Chat Server for SIH Application
REM This script starts the WebSocket server on localhost:8000

echo.
echo ======================================
echo  SIH Chat & Announcement Server
echo ======================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://www.python.org
    pause
    exit /b 1
)

echo Python found: 
python --version

REM Navigate to SIH directory
cd /d D:\Softwares\wamp64\www\SIH

echo.
echo Checking dependencies...
pip list | findstr fastapi >nul
if errorlevel 1 (
    echo Installing required packages...
    pip install -r requirements.txt
    if errorlevel 1 (
        echo ERROR: Failed to install dependencies
        pause
        exit /b 1
    )
)

echo.
echo ======================================
echo  Starting FastAPI Server...
echo ======================================
echo.
echo Server will run on: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start the server
python chat_server.py

if errorlevel 1 (
    echo.
    echo ERROR: Failed to start server
    echo Check the error message above
    pause
)
