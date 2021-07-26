<?php
echo "<script>alert();</script>";
die;
class Sg_Apis{
	public function __construct(){
        global $wpdb;
        add_action( 'rest_api_init',array($this,"sg_myRestApiRoutes"));
        
    }
	public function sg_myRestApiRoutes(){
    register_rest_route( 'sg/v1', '/login_user/', array(
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this,'sg_login_user'),
				'args' => array(
					'id' => array(
						//'validate_callback' => 'is_numeric'
					),
				)
			)
		) );
    
    }
    // login_user function
    
    public function sg_login_user(){
    echo "Hi there";
    
    }
    
    
    
    

}


?>