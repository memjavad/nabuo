## Performance Improvements

* When addressing N+1 query problems fetching post metadata across a collection of posts, use `wp_list_pluck` to extract post IDs and `update_meta_cache('post', $post_ids)` to pre-warm the cache. Subsequent calls to `get_post_meta($id, $key, true)` within a loop will then hit the cache instead of the database.
* Always use `update_meta_cache` for WordPress metadata, rather than directly fetching and building an associative array manually using `$wpdb->get_results`.
