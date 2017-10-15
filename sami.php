<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.1
 * @license MIT
 */

use Sami\{
	Sami,
	RemoteRepository\GitHubRemoteRepository
};

use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
	->files()
	->name('*.php')
	->in(__DIR__ . '/src');

return new Sami($iterator, [
	'title'             => 'PHP DateRange',
	'build_dir'         => __DIR__ . '/docs',
	'cache_dir'         => __DIR__ . '/.pocket/cache',
	'remote_repository' => new GitHubRemoteRepository('jasonjgardner/date-range', __DIR__)
]);
