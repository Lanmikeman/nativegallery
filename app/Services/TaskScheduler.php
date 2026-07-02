<?php

namespace App\Services;

class TaskScheduler
{
    public function __construct() {}

    public function cronScriptPath(string $handler): string
    {
        return rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . $handler;
    }

    public function cronJobCommand(string $handler): string
    {
        $logsDir = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\')
            . (NGALLERY['root']['logslocation'] ?? '/logs');

        return 'php ' . $this->cronScriptPath($handler)
            . ' >> ' . $logsDir . '/cron.log 2>&1';
    }

    public function addTask(string $taskName, string $command, string $interval = '*/5 * * * *', ?string $handler = null): array
    {
        $handler = $handler ?? $this->handlerFromCommand($command);
        if ($handler === null) {
            return ['ok' => false, 'message' => 'Не удалось определить путь к задаче'];
        }

        return $this->isWindows()
            ? $this->wrapResult($this->addWindowsTask($taskName, $command), $taskName, $handler, 'windows')
            : $this->addLinuxTask($command, $interval, $handler, $taskName);
    }

    public function isTaskExists(?string $taskName = null, ?string $command = null, ?string $handler = null): bool
    {
        if ($this->isWindows()) {
            return $this->isWindowsTaskExists($taskName);
        }

        $handler = $handler ?? $this->handlerFromCommand($command);
        if ($handler === null) {
            return false;
        }

        return $this->isLinuxCronInstalled($handler, $taskName);
    }

    public function removeTask(?string $taskName = null, ?string $command = null, ?string $handler = null): array
    {
        $handler = $handler ?? $this->handlerFromCommand($command);
        if ($handler === null) {
            return ['ok' => false, 'message' => 'Не удалось определить путь к задаче'];
        }

        if ($this->isWindows()) {
            return $this->wrapResult($this->removeWindowsTask($taskName), $taskName, $handler, 'windows', false);
        }

        return $this->removeLinuxTask($command, $handler, $taskName);
    }

    public function getTaskStatus(string $taskName, ?string $command = null, ?string $handler = null): string
    {
        $handler = $handler ?? $this->handlerFromCommand($command);
        if ($handler === null) {
            return '❌ Не работает (задача отсутствует)';
        }

        if (!$this->isLinuxCronInstalled($handler, $taskName)) {
            return '❌ Не работает (задача отсутствует)';
        }

        if ($this->isWindows()) {
            return $this->getWindowsTaskStatus($taskName);
        }

        $source = $this->detectLinuxCronSource($handler);
        if ($source === 'cron.d') {
            return '✅ Настроена в /etc/cron.d/';
        }
        if ($source === 'crontab') {
            return '✅ Настроена в crontab';
        }

        return '✅ Настроена в cron';
    }

    /** @param array<int, array<string, mixed>> $array */
    public function findHandlerById(array $array, string $id): ?string
    {
        foreach ($array as $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                return $item['handler'] ?? null;
            }
        }

        return null;
    }

    private function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    private function normalizeHandler(string $handler): string
    {
        return str_replace('\\', '/', $handler);
    }

    private function handlerFromCommand(?string $command): ?string
    {
        if ($command === null || $command === '') {
            return null;
        }

        $path = preg_replace('/^php\s+/i', '', trim($command));
        $path = preg_replace('/\s*>>.*$/', '', $path);
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        $docRoot = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\');
        if (str_starts_with($path, $docRoot)) {
            return $this->normalizeHandler(substr($path, strlen($docRoot)));
        }

        foreach (NGALLERY_TASKS as $task) {
            if (!empty($task['handler']) && str_contains($path, basename((string) $task['handler']))) {
                return $this->normalizeHandler((string) $task['handler']);
            }
        }

        return null;
    }

    private function cronMarkerPath(): string
    {
        return rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . '/storage/cron-tasks.json';
    }

    /** @return array<string, array<string, mixed>> */
    private function loadCronMarkers(): array
    {
        $path = $this->cronMarkerPath();
        if (!is_readable($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    private function saveCronMarker(string $taskId, string $handler, string $source): void
    {
        $markers = $this->loadCronMarkers();
        $markers[$taskId] = [
            'handler' => $this->normalizeHandler($handler),
            'source' => $source,
            'installed_at' => time(),
        ];

        $path = $this->cronMarkerPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents(
            $path,
            json_encode($markers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            LOCK_EX
        );
        @chmod($path, 0664);
    }

    private function removeCronMarker(string $taskId): void
    {
        $markers = $this->loadCronMarkers();
        if (!isset($markers[$taskId])) {
            return;
        }

        unset($markers[$taskId]);
        $path = $this->cronMarkerPath();
        if ($markers === []) {
            @unlink($path);
            return;
        }

        @file_put_contents(
            $path,
            json_encode($markers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            LOCK_EX
        );
    }

    private function isMarkerInstalled(string $taskId): bool
    {
        return isset($this->loadCronMarkers()[$taskId]);
    }

    private function lineMatchesHandler(string $line, string $handler): bool
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            return false;
        }

        $suffix = $this->normalizeHandler($handler);
        $scriptName = basename($suffix);

        return str_contains($line, $suffix) || str_contains($line, $scriptName);
    }

    private function isLinuxCronInstalled(string $handler, ?string $taskId = null): bool
    {
        if ($taskId !== null && $this->isMarkerInstalled($taskId)) {
            return true;
        }

        if ($this->detectLinuxCronSource($handler) !== null) {
            return true;
        }

        return false;
    }

    private function detectLinuxCronSource(string $handler): ?string
    {
        foreach ($this->linuxCronLines() as $source => $lines) {
            foreach ($lines as $line) {
                if ($this->lineMatchesHandler($line, $handler)) {
                    return $source;
                }
            }
        }

        return null;
    }

    /** @return array<string, list<string>> */
    private function linuxCronLines(): array
    {
        $sources = [];

        exec('crontab -l 2>/dev/null', $userCrontab);
        $sources['crontab'] = is_array($userCrontab) ? $userCrontab : [];

        exec('crontab -u www-data -l 2>/dev/null', $wwwDataCrontab);
        if (is_array($wwwDataCrontab) && $wwwDataCrontab !== []) {
            $sources['crontab'] = array_merge($sources['crontab'], $wwwDataCrontab);
        }

        $cronD = [];
        foreach (glob('/etc/cron.d/*') ?: [] as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }
            foreach (explode("\n", $content) as $line) {
                $cronD[] = $line;
            }
        }
        $sources['cron.d'] = $cronD;

        $systemCrontab = @file_get_contents('/etc/crontab');
        $sources['system'] = $systemCrontab === false ? [] : explode("\n", $systemCrontab);

        return $sources;
    }

    private function addLinuxTask(string $command, string $interval, string $handler, string $taskId): array
    {
        if ($this->isLinuxCronInstalled($handler, $taskId)) {
            return ['ok' => true, 'message' => 'Cron-задача уже установлена'];
        }

        $cronJob = trim($interval) . ' ' . $command;
        exec('crontab -l 2>/dev/null', $output, $returnVar);
        if ($returnVar !== 0) {
            $output = [];
        }

        $output[] = $cronJob;
        $tmp = tempnam(sys_get_temp_dir(), 'ng_cron_');
        if ($tmp === false) {
            return ['ok' => false, 'message' => 'Не удалось создать временный файл для crontab'];
        }

        file_put_contents($tmp, implode(PHP_EOL, $output) . PHP_EOL);
        exec('crontab ' . escapeshellarg($tmp) . ' 2>&1', $execOut, $execCode);
        @unlink($tmp);

        if ($execCode === 0 && $this->detectLinuxCronSource($handler) === 'crontab') {
            $this->saveCronMarker($taskId, $handler, 'crontab');
            return ['ok' => true, 'message' => 'Cron-задача добавлена в crontab'];
        }

        return [
            'ok' => false,
            'message' => 'Не удалось добавить задачу в crontab от имени www-data. '
                . 'Установите cron на сервере: sudo NG_WEB_ROOT='
                . rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\')
                . ' bash deploy/setup-cron.sh'
                . ($execOut !== [] ? ' (' . implode(' ', $execOut) . ')' : ''),
        ];
    }

    private function removeLinuxTask(?string $command, string $handler, string $taskId): array
    {
        $this->removeCronMarker($taskId);

        if ($this->detectLinuxCronSource($handler) === 'cron.d') {
            return [
                'ok' => false,
                'message' => 'Задача установлена в /etc/cron.d/ и не может быть удалена из админки. '
                    . 'Удалите файл /etc/cron.d/nativegallery вручную или через deploy/setup-cron.sh',
            ];
        }

        exec('crontab -l 2>/dev/null', $output, $returnVar);
        if ($returnVar !== 0) {
            return ['ok' => true, 'message' => 'Cron-задача удалена'];
        }

        $suffix = $this->normalizeHandler($handler);
        $filtered = array_filter($output, function (string $line) use ($command, $suffix): bool {
            if ($command !== null && $command !== '' && str_contains($line, $command)) {
                return false;
            }

            return !$this->lineMatchesHandler($line, $suffix);
        });

        $tmp = tempnam(sys_get_temp_dir(), 'ng_cron_');
        if ($tmp === false) {
            return ['ok' => false, 'message' => 'Не удалось создать временный файл для crontab'];
        }

        file_put_contents($tmp, implode(PHP_EOL, $filtered) . PHP_EOL);
        exec('crontab ' . escapeshellarg($tmp) . ' 2>&1', $execOut, $execCode);
        @unlink($tmp);

        if ($execCode !== 0) {
            return ['ok' => false, 'message' => 'Не удалось обновить crontab: ' . implode(' ', $execOut)];
        }

        return ['ok' => true, 'message' => 'Cron-задача удалена из crontab'];
    }

    /** @return array{ok: bool, message: string} */
    private function wrapResult(string $message, string $taskId, string $handler, string $source, bool $install = true): array
    {
        $ok = !str_starts_with($message, '❌');
        if ($ok && $install) {
            $this->saveCronMarker($taskId, $handler, $source);
        }
        if ($ok && !$install) {
            $this->removeCronMarker($taskId);
        }

        return ['ok' => $ok, 'message' => $message];
    }

    private function addWindowsTask(string $taskName, string $command): string
    {
        if ($this->isWindowsTaskExists($taskName)) {
            return '✅ Задача уже существует в Windows.';
        }

        $cmd = 'schtasks /Create /SC MINUTE /MO 5 /TN "' . $taskName . '" /TR "' . $command . '" /F';
        exec($cmd, $output, $returnCode);

        return ($returnCode === 0) ? '✅ Задача добавлена в Windows!' : '❌ Ошибка при добавлении задачи.';
    }

    private function isWindowsTaskExists(?string $taskName): bool
    {
        exec('schtasks /Query /TN "' . $taskName . '" 2>&1', $output, $returnVar);
        return $returnVar === 0;
    }

    private function removeWindowsTask(?string $taskName): string
    {
        if (!$this->isWindowsTaskExists($taskName)) {
            return '❌ Задача не найдена в Windows.';
        }

        exec('schtasks /Delete /TN "' . $taskName . '" /F', $output, $returnCode);
        return ($returnCode === 0) ? '✅ Задача удалена из Windows!' : '❌ Ошибка при удалении задачи.';
    }

    private function getWindowsTaskStatus(string $taskName): string
    {
        exec('schtasks /Query /TN "' . $taskName . '" /FO LIST /V', $output, $returnVar);

        if ($returnVar !== 0) {
            return '❌ Не работает (задача отсутствует)';
        }

        $status = '⚠ Не работает (ошибка: неизвестно)';
        $output = array_map(static function ($line) {
            return is_string($line) ? iconv('Windows-1251', 'UTF-8', $line) : '';
        }, $output);

        foreach ($output as $line) {
            if (strpos($line, 'Статус:') !== false) {
                if (stripos($line, 'Выполняется') !== false) {
                    $status = '✅ Работает корректно';
                } elseif (stripos($line, 'Готово') !== false) {
                    $status = '⚠ Не работает (но активна)';
                } elseif (stripos($line, 'Не удалось запустить') !== false) {
                    $status = '⚠ Не работает (ошибка: не удалось запустить)';
                }
                break;
            }
        }

        return $status;
    }
}