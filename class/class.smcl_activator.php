<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


class SMCL_Activator{

	public function __construct(){
	}

	// activate
	public function smActivate(){
		#active info
	}

	// deactivate
	public function smDeActive(){
	    delete_option( '_smcl-options' );
	}

}
?>