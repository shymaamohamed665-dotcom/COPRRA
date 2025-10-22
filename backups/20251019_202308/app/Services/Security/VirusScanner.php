<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class VirusScanner
{
    public function isEnabled(): bool
    {
        return (bool) (config('security.uploads.scan_for_viruses', true));
    }

    /**
     * Scan the uploaded file for viruses and disallowed PHP content.
     * Throws \RuntimeException on detection or failure.
     */
    public function scan(UploadedFile $file): void
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        if (! $path || ! is_file($path)) {
            throw new \RuntimeException('Invalid upload file path.');
        }

        // Block PHP content by MIME or signature regardless of extension.
        $mime = $this->detectMime($path);
        if ($this->isPhpMime($mime) || $this->containsPhpCode($path)) {
            throw new \RuntimeException('PHP content is not allowed in uploads.');
        }

        // Optional antivirus scan using clamscan/clamdscan when enabled.
        if ($this->isEnabled()) {
            $binary = env('CLAMAV_PATH', 'clamscan');
            $args = [$binary, '--no-summary', $path];
            $process = new Process($args);
            $process->setTimeout(30);
            $process->run();

            // clamscan: 0 clean, 1 infected, 2 error; treat any non-zero as failure
            $exit = $process->getExitCode();
            if ($exit !== 0) {
                if ($exit === 1) {
                    throw new \RuntimeException('File is infected (virus detected).');
                }
                Log::warning('Virus scan failed', [
                    'binary' => $binary,
                    'exit_code' => $exit,
                    'error' => $process->getErrorOutput(),
                ]);

                throw new \RuntimeException('Virus scan failed. Upload rejected.');
            }
        }
    }

    private function detectMime(string $path): string
    {
        $mime = '';
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $tmp = finfo_file($finfo, $path);
            if (is_string($tmp)) {
                $mime = $tmp;
            }
            finfo_close($finfo);
        }

        return $mime !== '' ? $mime : 'application/octet-stream';
    }

    private function isPhpMime(string $mime): bool
    {
        return in_array($mime, [
            'text/x-php',
            'application/x-httpd-php',
            'application/php',
        ], true);
    }

    private function containsPhpCode(string $path): bool
    {
        // Read a small chunk to look for PHP open tags
        $fp = fopen($path, 'rb');
        if ($fp === false) {
            return false;
        }
        $chunk = fread($fp, 8192);
        fclose($fp);

        if (! is_string($chunk) || $chunk === '') {
            return false;
        }

        return (bool) preg_match('/<\?(php|=)/i', $chunk);
    }
}
