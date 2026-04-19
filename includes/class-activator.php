<?php

namespace ArabPsychology\NabooDatabase;

use ArabPsychology\NabooDatabase\Core\CPT;

class Activator {

	public static function activate() {
		// Create custom post types and taxonomies upon activation
		// We can't rely on the autoloader yet if this runs before plugins_loaded or similar, 
        // but typically activation hooks run in a context where we might need to be careful.
        // However, since we registered the autoloader in the main file, it should be available.
        
		$cpt = new CPT();
		$cpt->register();
		
		flush_rewrite_rules();
	}

}
