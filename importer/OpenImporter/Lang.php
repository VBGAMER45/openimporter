<?php
/**
 * @name      OpenImporter
 * @copyright OpenImporter contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 2.0 Alpha
 */

namespace OpenImporter\Core;

/**
 * Class Lang
 * loads the appropriate language file(s) if they exist.
 *
 * The default import_en.xml file contains the English strings used by the
 * importer.
 */
class Lang
{
	/**
	 * The array holding the strings
	 *
	 * @var string[]
	 */
	protected $_lang = array();

	/**
	 * Loads the language xml file.
	 *
	 * @param string $path
	 *
	 * @throws \Exception if it cannot find the XML file.
	 * @throws ImportException if the XML file has got a corrupted structure.
	 */
	public function loadLang($path)
	{
		// Detect the browser language
		$language = $this->detectBrowserLanguage();
		$language_file = $this->findLanguage($path, $language);

		// Ouch, we really should never arrive here..
		if (!$language_file)
		{
			throw new \Exception('Unable to detect language file!');
		}

		// Silence simplexml errors because we take care of them by ourselves
		libxml_use_internal_errors(true);

		// Load the language file
		if (!$langObj = simplexml_load_file($language_file, 'SimpleXMLElement', LIBXML_NOCDATA))
		{
			throw new ImportException('XML-Syntax error in file: ' . $language_file);
		}

		foreach ($langObj as $strings)
		{
			$this->set((string) $strings->attributes()->{'name'}, (string) $strings);
		}
	}

	/**
	 * This is used to detect the Client's browser language.
	 *
	 * @return string[] the shortened string of the browser's language.
	 */
	protected function detectBrowserLanguage()
	{
		$preferred = array();

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			// Break up string into pieces (languages and q factors)
			// the string looks like: en-GB,en;q=0.9,it;q=0.8
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), $lang_parse);

			if (count($lang_parse[1]))
			{
				// Create a list like "en" => 0.8
				$preferred = array_combine($lang_parse[1], $lang_parse[4]);

				// Set default to 1 for any without q factor (IE fix)
				foreach ($preferred as $lang => $val)
				{
					if ($val === '')
					{
						$preferred[$lang] = 1;
					}
				}

				// Sort list based on value
				arsort($preferred, SORT_NUMERIC);
			}
		}

		return array_keys($preferred);
	}

	/**
	 * Finds out if the language we are looking for exists or not.
	 *
	 * @param string $path The path to look for the language file
	 * @param string[] $language The name of the language
	 *
	 * @return bool
	 */
	protected function findLanguage($path, $language)
	{
		$language_file = false;

		// Loop through the preferred languages and try to find the related language file
		foreach ($language as $key)
		{
			if (file_exists($path . '/import_' . $key . '.xml'))
			{
				$language_file = $path . '/import_' . $key . '.xml';
				break;
			}
		}

		// English is still better than nothing
		if (empty($language_file))
		{
			if (file_exists($path . '/import_en.xml'))
			{
				$language_file = $path . '/import_en.xml';
			}
		}

		return $language_file;
	}

	/**
	 * Adds a new variable to lang.
	 *
	 * @param string $key Name of the variable
	 * @param string $value Value of the variable
	 *
	 * @throws \Exception
	 * @return boolean|null
	 */
	protected function set($key, $value)
	{
		try
		{
			if ($this->has($key))
			{
				throw new \Exception('Unable to set language string for <em>' . $key . '</em>. It was already set.');
			}

			$this->_lang[$key] = $value;

			return true;
		}
		catch (Exception $e)
		{
			// @todo this should not be a fatal error
			ImportException::exceptionHandler($e);
		}
	}

	/**
	 * Tests if given $key exists in lang
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has($key)
	{
		return isset($this->_lang[$key]);
	}

	/**
	 * Getter
	 *
	 * @param string|int $key
	 *
	 * @return string|int|bool|null|object
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Returns the value of the specified $key in lang.
	 *
	 * @param string|mixed[] $key Name of the variable
	 *
	 * @return string|null Value of the specified $key
	 */
	public function get($key)
	{
		if (is_array($key))
		{
			$l_key = array_shift($key);

			if ($this->has($l_key))
			{
				return vsprintf($this->_lang[$l_key], $key);
			}
			else
			{
				return array_pop($key);
			}
		}
		else
		{
			if ($this->has($key))
			{
				return $this->_lang[$key];
			}
			else
			{
				return $key;
			}
		}
	}

	/**
	 * Tests if the key is set.
	 *
	 * @param string|int $key
	 *
	 * @return bool
	 */
	public function __isset($key)
	{
		return array_key_exists($key, $this->_lang);
	}

	/**
	 * Returns the whole lang as an array.
	 *
	 * @return array Whole lang
	 */
	public function getAll()
	{
		return $this->_lang;
	}
}