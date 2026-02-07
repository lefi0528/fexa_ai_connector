<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Command;

use const PHP_EOL;
use function assert;
use function sprintf;
use function version_compare;
use PHPUnit\Util\Http\Downloader;


final class VersionCheckCommand implements Command
{
    private readonly Downloader $downloader;
    private readonly int $majorVersionNumber;
    private readonly string $versionId;

    public function __construct(Downloader $downloader, int $majorVersionNumber, string $versionId)
    {
        $this->downloader         = $downloader;
        $this->majorVersionNumber = $majorVersionNumber;
        $this->versionId          = $versionId;
    }

    public function execute(): Result
    {
        $latestVersion = $this->downloader->download('https://phar.phpunit.de/latest-version-of/phpunit');

        assert($latestVersion !== false);

        $latestCompatibleVersion = $this->downloader->download('https://phar.phpunit.de/latest-version-of/phpunit-' . $this->majorVersionNumber);

        assert($latestCompatibleVersion !== false);

        $notLatest           = version_compare($latestVersion, $this->versionId, '>');
        $notLatestCompatible = version_compare($latestCompatibleVersion, $this->versionId, '>');

        if (!$notLatest && !$notLatestCompatible) {
            return Result::from(
                'You are using the latest version of PHPUnit.' . PHP_EOL,
            );
        }

        $buffer = 'You are not using the latest version of PHPUnit.' . PHP_EOL;

        if ($notLatestCompatible) {
            $buffer .= sprintf(
                'The latest version compatible with PHPUnit %s is PHPUnit %s.' . PHP_EOL,
                $this->versionId,
                $latestCompatibleVersion,
            );
        }

        if ($notLatest) {
            $buffer .= sprintf(
                'The latest version is PHPUnit %s.' . PHP_EOL,
                $latestVersion,
            );
        }

        return Result::from($buffer);
    }
}
