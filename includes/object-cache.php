<?php
/**
 * Plugin Name: Naboo Memcached Object Cache
 * Description: Memcached backend for the WP Object Cache, managed by Naboo Database.
 * Version: 1.0.0
 * 
 * Install this file to wp-content/object-cache.php
 */

if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
	define( 'WP_CACHE_KEY_SALT', '' );
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, $expire );
}

function wp_cache_incr( $key, $n = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->incr( $key, $n, $group );
}

function wp_cache_decr( $key, $n = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decr( $key, $n, $group );
}

function wp_cache_close() {
	global $wp_object_cache;
	return $wp_object_cache->close();
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group );
}

function wp_cache_flush() {
	global $wp_object_cache;
	return $wp_object_cache->flush();
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new Naboo_Object_Cache();
}

function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->replace( $key, $data, $group, $expire );
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	if ( defined( 'WP_INSTALLING' ) ) {
		return $wp_object_cache->delete( $key, $group );
	}
	return $wp_object_cache->set( $key, $data, $group, $expire );
}

function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;
	return $wp_object_cache->switch_to_blog( $blog_id );
}

function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_global_groups( $groups );
}

function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_non_persistent_groups( $groups );
}

class Naboo_Object_Cache {
	private $mc = null;
	private $cache = array();
	private $global_groups = array();
	private $no_mc_groups = array();
	private $cache_hits = 0;
	private $cache_misses = 0;
	private $blog_id;

	public function __construct() {
		$this->blog_id = ( is_multisite() ) ? get_current_blog_id() : '';
		
		if ( class_exists( 'Memcached' ) ) {
			$this->mc = new Memcached();
			$this->mc->addServer( '127.0.0.1', 11211 );
		} elseif ( class_exists( 'Memcache' ) ) {
			$this->mc = new Memcache();
			$this->mc->addServer( '127.0.0.1', 11211 );
		}
	}

	private function get_key( $id, $group ) {
		if ( empty( $group ) ) { $group = 'default'; }
		$prefix = ( ! in_array( $group, $this->global_groups ) ) ? $this->blog_id : '';
		return WP_CACHE_KEY_SALT . ':' . $prefix . ':' . $group . ':' . $id;
	}

	public function add( $id, $data, $group = 'default', $expire = 0 ) {
		$key = $this->get_key( $id, $group );
		if ( in_array( $group, $this->no_mc_groups ) ) {
			$this->cache[$key] = $data;
			return true;
		}
		if ( $this->mc ) {
			return $this->mc->add( $key, $data, $expire );
		}
		$this->cache[$key] = $data;
		return true;
	}

	public function set( $id, $data, $group = 'default', $expire = 0 ) {
		$key = $this->get_key( $id, $group );
		if ( in_array( $group, $this->no_mc_groups ) ) {
			$this->cache[$key] = $data;
			return true;
		}
		if ( $this->mc ) {
			return $this->mc->set( $key, $data, $expire );
		}
		$this->cache[$key] = $data;
		return true;
	}

	public function get( $id, $group = 'default', $force = false, &$found = null ) {
		$key = $this->get_key( $id, $group );
		if ( isset( $this->cache[$key] ) && ! $force ) {
			$found = true;
			return $this->cache[$key];
		}
		if ( $this->mc ) {
			$value = $this->mc->get( $key );
			if ( $this->mc instanceof Memcached && $this->mc->getResultCode() === Memcached::RES_NOTFOUND ) {
				$found = false;
				return false;
			}
			$found = ( $value !== false );
			return $value;
		}
		$found = false;
		return false;
	}

	public function delete( $id, $group = 'default' ) {
		$key = $this->get_key( $id, $group );
		unset( $this->cache[$key] );
		if ( $this->mc ) {
			return $this->mc->delete( $key );
		}
		return true;
	}

	public function flush() {
		$this->cache = array();
		if ( $this->mc ) {
			return $this->mc->flush();
		}
		return true;
	}

	public function add_global_groups( $groups ) { $this->global_groups = array_merge( $this->global_groups, (array) $groups ); }
	public function add_non_persistent_groups( $groups ) { $this->no_mc_groups = array_merge( $this->no_mc_groups, (array) $groups ); }
	public function switch_to_blog( $blog_id ) { $this->blog_id = $blog_id; }
	public function close() { return true; }
	public function incr( $id, $n = 1, $group = 'default' ) { return false; }
	public function decr( $id, $n = 1, $group = 'default' ) { return false; }
	public function replace( $id, $data, $group = 'default', $expire = 0 ) { return $this->set( $id, $data, $group, $expire ); }
}
