<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->runtimeRoot = sys_get_temp_dir().'/bulla runtime '.bin2hex(random_bytes(8));
    $this->entrypoint = dirname(__DIR__, 2).'/deploy/entrypoint.sh';

    (new Filesystem)->makeDirectory($this->runtimeRoot, 0750, true);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->runtimeRoot);
});

it('prepares empty runtime directories and restricts newly created files', function (): void {
    $process = new Process([
        'sh', $this->entrypoint, PHP_BINARY, '-r',
        'file_put_contents(getenv("ROOT")."/storage/logs/startup.log", "started");',
    ], env: ['ROOT' => $this->runtimeRoot]);

    $process->mustRun();

    foreach ([
        'storage/app/private',
        'storage/app/public',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ] as $directory) {
        expect(is_dir($this->runtimeRoot.'/'.$directory))->toBeTrue();
    }

    expect(file_get_contents($this->runtimeRoot.'/storage/logs/startup.log'))->toBe('started')
        ->and(fileperms($this->runtimeRoot.'/storage/logs/startup.log') & 0007)->toBe(0);
});

it('preserves existing runtime data across restarts', function (): void {
    $process = new Process(['sh', $this->entrypoint, 'true'], env: ['ROOT' => $this->runtimeRoot]);
    $process->mustRun();

    $path = $this->runtimeRoot.'/bootstrap/cache/config.php';
    file_put_contents($path, '<?php return ["key" => "keep-me"];');
    chmod($path, 0640);

    $process->mustRun();

    expect(file_get_contents($path))->toBe('<?php return ["key" => "keep-me"];')
        ->and(fileperms($path) & 0777)->toBe(0640);
});

it('passes command arguments literally and preserves the exit status', function (): void {
    $argument = 'literal argument $(touch unexpected)';
    $process = new Process([
        'sh', $this->entrypoint, PHP_BINARY, '-r',
        'fwrite(STDOUT, $argv[1]); exit(23);', $argument,
    ], env: ['ROOT' => $this->runtimeRoot]);

    $process->run();

    expect($process->getOutput())->toBe($argument)
        ->and($process->getExitCode())->toBe(23);
});

it('does not start the command when runtime initialization fails', function (): void {
    file_put_contents($this->runtimeRoot.'/storage', 'not a directory');

    $process = new Process([
        'sh', $this->entrypoint, PHP_BINARY, '-r', 'fwrite(STDOUT, "started");',
    ], env: ['ROOT' => $this->runtimeRoot]);

    $process->run();

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getOutput())->toBe('')
        ->and($process->getErrorOutput())->not->toBeEmpty();
});
