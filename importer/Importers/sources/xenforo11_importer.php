<?php
/**
 * @name      OpenImporter
 * @copyright OpenImporter contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 2.0 Alpha
 */

namespace OpenImporter\Importers\sources;

class XenForo1_1 extends \OpenImporter\Importers\AbstractSourceImporter
{
	protected $setting_file = '/includes/config.php';

	public function getName()
	{
		return 'Wordpress 3.x';
	}

	public function getVersion()
	{
		return 'ElkArte 1.0';
	}

	public function getPrefix()
	{
		global $xf_prefix;

		return '`' . $this->getDbName() . '`.' . $xf_prefix;
	}

	public function getDbName()
	{
		global $xf_database;

		return $xf_database;
	}

	public function getTableTest()
	{
		return 'user';
	}

	/**
	 * From here on, all the methods are needed helper for the conversion
	 */
	public function preparseMembers($originalRows)
	{
		$rows = array();
		foreach ($originalRows as $row)
		{
			$pass = unserialize($row['tmp']);

			if (isset($pass['hash']))
				$row['passwd'] = $pass['hash'];
			else
				$row['passwd'] = sha1(md5(mktime()));

			if	(isset($pass['salt']))
				$row['password_salt'] = $pass['salt'];
			else
				$row['password_salt'] = '';
			unset($row['tmp']);

			$rows[] = $row;
		}

		return $rows;
	}

	public function preparseBoards($originalRows)
	{
		$rows = array();
		foreach ($originalRows as $row)
		{
			$request = $this->db->query("
				SELECT
					thread_id, last_post_id
				FROM {$this->config->from_prefix}thread
				WHERE node_id  = $row[id_board]
				ORDER BY thread_id DESC
				LIMIT 1");

				list(, $row['id_last_msg']) = $this->db->fetch_row($request);
				$this->db->free_result($request);

			$rows[] = $row;
		}

		return $rows;
	}
}