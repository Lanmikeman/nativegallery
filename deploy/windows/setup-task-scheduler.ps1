# NativeGallery — Windows Task Scheduler (replaces Linux cron for contests)
#
# Usage (Administrator):
#   .\deploy\windows\setup-task-scheduler.ps1
#   .\deploy\windows\setup-task-scheduler.ps1 -WebRoot "C:\inetpub\nativegallery" -PhpExe "C:\php\php.exe"

param(
    [string]$WebRoot = "C:\inetpub\nativegallery",
    [string]$PhpExe = "C:\php\php.exe",
    [string]$TaskName = "NativeGallery-ExecContests"
)

$ErrorActionPreference = "Stop"

$script = Join-Path $WebRoot "app\Controllers\Exec\Tasks\ExecContests.php"
$logDir = Join-Path $WebRoot "logs"

if (-not (Test-Path $script)) {
    Write-Error "ExecContests.php not found: $script"
}
if (-not (Test-Path $PhpExe)) {
    Write-Error "PHP not found: $PhpExe"
}

New-Item -ItemType Directory -Force -Path $logDir | Out-Null

$action = New-ScheduledTaskAction -Execute $PhpExe -Argument "`"$script`" >> `"$logDir\cron.log`" 2>&1" -WorkingDirectory $WebRoot
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration ([TimeSpan]::MaxValue)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force | Out-Null

Write-Host "Task registered: $TaskName"
Write-Host "  Every 5 minutes: $PhpExe $script"
Write-Host "  Log: $logDir\cron.log"
Write-Host "  WebRoot: $WebRoot"
Write-Host ""
Write-Host "Test: & `"$PhpExe`" `"$script`""