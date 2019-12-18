<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\Common;
use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;
use Piwik\Updater\Migration;
use Piwik\Updater\Migration\Factory as MigrationFactory;

/**
 * Update for version 4.0.0-b1.
 */
class Updates_4_0_0_b1 extends PiwikUpdates
{
    /**
     * @var MigrationFactory
     */
    private $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        $migration1 = $this->migration->db->changeColumnType('log_action', 'name', 'VARCHAR(4096)');
        $migration2 = $this->migration->db->changeColumnType('log_conversion', 'url', 'VARCHAR(4096)');

        // Move the site search fields of log_visit out of custom variables into their own fields
        $createSearchCatColumn = $this->migration->db->addColumn('log_link_visit_action', 'search_cat', 'VARCHAR(200)');
        $createSearchCountColumn = $this->migration->db->addColumn('log_link_visit_action', 'search_count', 'VARCHAR(200)');
        $visitActionTable = Common::prefixTable('log_link_visit_action');
        $populateSearchCatColumn = $this->migration->db->boundSql("UPDATE $visitActionTable SET search_cat = custom_var_v4 WHERE custom_var_k4 = '_pk_scat'");
        $populateSearchCountColumn = $this->migration->db->boundSql("UPDATE $visitActionTable SET search_count = custom_var_v5 WHERE custom_var_k4 = '_pk_scount'");

        return array(
            $migration1,
            $migration2,
            $createSearchCatColumn,
            $createSearchCountColumn,
            $populateSearchCatColumn,
            $populateSearchCountColumn
        );
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
