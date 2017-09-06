<?php
use \Kohana\Cache as Cache;




/*
 * Kohana\Request -> $_cache
	 * @var    Cache  Caching library for request caching
 *
* protected $_cache;
 * */

/* -> execute() :: EVENT_EXECUTE
		if (($cache = $this->cache()) instanceof HTTP_Cache)
			return $cache->execute($this, $request, $response); */

/*
 * 	/**
	 * Getter and setter for the internal caching engine,
	 * used to cache responses if available and valid.
	 *
	 * @param   HTTP_Cache  $cache  engine to use for caching
	 * @return  HTTP_Cache
	 * @return  Request_Client
	 */
/*
public function cache(HTTP_Cache $cache = NULL)
{
  if ($cache === NULL)
    return $this->_cache;

  $this->_cache = $cache;
  return $this;
}
 */

/*
 * 	public function assign_client_properties(Client $client)
s	{
		$client->cache($this->cache());
}
 */