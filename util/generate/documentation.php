<?php
/**
 * Generate documentation
 *
 * To actually create the documentation itself, run this command:
 *
 *   php lib/external/packages/vendor/sami/sami/sami.php update util/generate/documentation.php
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

require_once dirname(__FILE__) . '/../../config/global.php';

echo 'This script needs some attention before it can be used. Please fix it.' . "\n";
exit(1);

use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('config/Config.php')
    ->exclude('config/Config_Routes.php')
    ->exclude('lib/external')
    ->exclude('store')
    ->exclude('po')
    ->exclude('tmp')
    ->exclude('tools')
    ->exclude('webroot')
    ->in(ROOT_PATH)
;

$configuration = array (
	'build_dir' => '/path/to/store/documentation',
	'cache_dir' => '/path/to/cache/documentation',
);

return new Sami\Sami($iterator, $configuration);
