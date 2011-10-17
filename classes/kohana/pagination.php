<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Pagination links generator.
 *
 * @package    Kohana/Pagination
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * 
 * @method Pagination current_page()
 * @method Pagination total_items()
 * @method Pagination items_per_page()
 * @method Pagination total_pages()
 * @method Pagination current_first_item()
 * @method Pagination current_last_item()
 * @method Pagination previous_page()
 * @method Pagination next_page()
 * @method Pagination first_page()
 * @method Pagination last_page()
 * @method Pagination offset()
 */
class Kohana_Pagination {

	/**
	 * @var array Merged configuration settings
	 */
	protected $config = array(
		'current_page'      => array('source' => 'query_string', 'key' => 'page'),
		'total_items'       => 0,
		'items_per_page'    => 10,
		'view'              => 'pagination/basic',
		'auto_hide'         => TRUE,
		'first_page_in_url' => FALSE,
	);
	
	/**
	 * @var array Members that have access methods
	 */
	protected $_properties = array(
		'current_page', 'total_items', 'items_per_page', 'total_pages', 'current_first_item', 'current_last_item',
		'previous_page', 'next_page', 'first_page', 'last_page', 'offset',
	);
	
	// Current page number
	protected $_current_page;

	// Total item count
	protected $_total_items;

	// How many items to show per page
	protected $_items_per_page;

	// Total page count
	protected $_total_pages;

	// Item offset for the first item displayed on the current page
	protected $_current_first_item;

	// Item offset for the last item displayed on the current page
	protected $_current_last_item;

	// Previous page number; FALSE if the current page is the first one
	protected $_previous_page;

	// Next page number; FALSE if the current page is the last one
	protected $_next_page;

	// First page number; FALSE if the current page is the first one
	protected $_first_page;

	// Last page number; FALSE if the current page is the last one
	protected $_last_page;

	// Query offset
	protected $_offset;

	/**
	 * Creates a new Pagination object.
	 *
	 * @param   array  configuration
	 * @return  Pagination
	 */
	public static function factory(array $config = array())
	{
		return new Pagination($config);
	}

	/**
	 * Creates a new Pagination object.
	 *
	 * @param   array  configuration
	 * @return  void
	 */
	public function __construct(array $config = array())
	{
		// Overwrite system defaults with application defaults
		$this->config = $this->config_group() + $this->config;

		// Pagination setup
		$this->setup($config);
	}

	/**
	 * Retrieves a pagination config group from the config file. One config group can
	 * refer to another as its parent, which will be recursively loaded.
	 *
	 * @param   string  pagination config group; "default" if none given
	 * @return  array   config settings
	 */
	public function config_group($group = 'default')
	{
		// Load the pagination config file
		$config_file = Kohana::$config->load('pagination');

		// Initialize the $config array
		$config['group'] = (string) $group;

		// Recursively load requested config groups
		while (isset($config['group']) AND isset($config_file->$config['group']))
		{
			// Temporarily store config group name
			$group = $config['group'];
			unset($config['group']);

			// Add config group values, not overwriting existing keys
			$config += $config_file->$group;
		}

		// Get rid of possible stray config group names
		unset($config['group']);

		// Return the merged config group settings
		return $config;
	}

	/**
	 * Loads configuration settings into the object and (re)calculates pagination if needed.
	 * Allows you to update config settings after a Pagination object has been constructed.
	 *
	 * @param   array   configuration
	 * @return  object  Pagination
	 */
	public function setup(array $config = array())
	{
		if (isset($config['group']))
		{
			// Recursively load requested config groups
			$config += $this->config_group($config['group']);
		}

		// Overwrite the current config settings
		$this->config = $config + $this->config;

		// Only (re)calculate pagination when needed
		if ($this->_current_page === NULL
			OR isset($config['current_page'])
			OR isset($config['total_items'])
			OR isset($config['items_per_page']))
		{
			// Retrieve the current page number
			if ( ! empty($this->config['current_page']['page']))
			{
				// The current page number has been set manually
				$this->_current_page = (int) $this->config['current_page']['page'];
			}
			else
			{
				switch ($this->config['current_page']['source'])
				{
					case 'query_string':
						$this->_current_page = isset($_GET[$this->config['current_page']['key']])
							? (int) $_GET[$this->config['current_page']['key']]
							: 1;
						break;

					case 'route':
						$this->_current_page = (int) Request::current()->param($this->config['current_page']['key'], 1);
						break;
				}
			}

			// Calculate and clean all pagination variables
			$this->_total_items        = (int) max(0, $this->config['total_items']);
			$this->_items_per_page     = (int) max(1, $this->config['items_per_page']);
			$this->_total_pages        = (int) ceil($this->_total_items / $this->_items_per_page);
			$this->_current_page       = (int) min(max(1, $this->_current_page), max(1, $this->_total_pages));
			$this->_current_first_item = (int) min((($this->_current_page - 1) * $this->_items_per_page) + 1, $this->_total_items);
			$this->_current_last_item  = (int) min($this->_current_first_item + $this->_items_per_page - 1, $this->_total_items);
			$this->_previous_page      = ($this->_current_page > 1) ? $this->_current_page - 1 : FALSE;
			$this->_next_page          = ($this->_current_page < $this->_total_pages) ? $this->_current_page + 1 : FALSE;
			$this->_first_page         = ($this->_current_page === 1) ? FALSE : 1;
			$this->_last_page          = ($this->_current_page >= $this->_total_pages) ? FALSE : $this->_total_pages;
			$this->_offset             = (int) (($this->_current_page - 1) * $this->_items_per_page);
		}

		// Chainable method
		return $this;
	}

	/**
	 * Generates the full URL for a certain page.
	 *
	 * @param   integer  page number
	 * @return  string   page URL
	 */
	public function url($page = 1)
	{
		// Clean the page number
		$page = max(1, (int) $page);

		// No page number in URLs to first page
		if ($page === 1 AND ! $this->config['first_page_in_url'])
		{
			$page = NULL;
		}

		switch ($this->config['current_page']['source'])
		{
			case 'query_string':
				return URL::site(Request::current()->uri()).URL::query(array($this->config['current_page']['key'] => $page));

			case 'route':
				return Route::url(
					Route::name(Request::current()->route()),
					array(
						$this->config['current_page']['key'] => $page,
						'controller' => Request::current()->controller(),
						'action' => Request::current()->action(),
						'directory' => Request::current()->directory(),
					) + Request::current()->param()
				) . URL::query();
		}

		return '#';
	}

	/**
	 * Checks whether the given page number exists.
	 *
	 * @param   integer  page number
	 * @return  boolean
	 * @since   3.0.7
	 */
	public function valid_page($page)
	{
		// Page number has to be a clean integer
		if ( ! Valid::digit($page))
			return FALSE;

		return $page > 0 AND $page <= $this->_total_pages;
	}

	/**
	 * Renders the pagination links.
	 *
	 * @param   mixed   string of the view to use, or a Kohana_View object
	 * @return  string  pagination output (HTML)
	 */
	public function render($view = NULL)
	{
		// Automatically hide pagination whenever it is superfluous
		if ($this->config['auto_hide'] === TRUE AND $this->_total_pages <= 1)
			return '';

		if ($view === NULL)
		{
			// Use the view from config
			$view = $this->config['view'];
		}

		if ( ! $view instanceof View)
		{
			// Load the view file
			$view = View::factory($view);
		}

		// Pass on the whole Pagination object
		return $view->set(get_object_vars($this))->set('page', $this)->render();
	}

	/**
	 * Renders the pagination links.
	 *
	 * @return  string  pagination output (HTML)
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch(Exception $e)
		{
			Kohana_Exception::handler($e);
			return '';
		}
	}

	/**
	 * Handles loading and setting properties.
	 *
	 * @param   string  $method Method name
	 * @param   array   $args   Method arguments
	 * @return  mixed
	 */
	public function __call($method, array $args)
	{
		if (in_array($method, $this->_properties))
		{
			if (!count($args))
			{
				return $this->{'_'.$method};
			}
		}
		else
		{
			throw new Kohana_Exception('Invalid method :method called in :class',
				array(':method' => $method, ':class' => get_class($this)));
		}
	}

	/**
	 * Handles setting of property
	 *
	 * @param  string $key    Property name
	 * @param  mixed  $value  Property value
	 * @return void
	 */
	public function __set($key, $value)
	{
		if (isset($this->{'_'.$key}))
		{
			$this->setup(array($key => $value));
		}
		else
		{
			throw new Kohana_Exception('The :property: property does not exist in the :class: class',
				array(':property:' => $key, ':class:' => get_class($this)));
		}
	}

} // End Pagination